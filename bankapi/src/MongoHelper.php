<?php
require __DIR__ . '/../vendor/autoload.php';

class MongoHelper {

	private static $_instance; //The single instance
  private static $bankDB;

  function __construct() {
    $mongoClient = new MongoDB\Client('mongodb://localhost:27017');
    self::$bankDB = $mongoClient->bank;
  }

  public static function getInstance() {
    if(!self::$_instance) { // If no instance then make one
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public static function createAccount($ownerName, $ownerId, $initialBalance) {
    $accountsCollection = self::$bankDB->accounts;
    $accountDocument = array(
      'ownerName' => $ownerName,
      'balance' => $initialBalance,
      'ownerId' => $ownerId,
      'active' => true
    );
    try {
      $result = $accountsCollection->insertOne($accountDocument);
      return array(
        'status' => 'success',
        'content' => $result->getInsertedId()
      );
    } catch(\Exception $e) {
      return array(
        'status' => 'fail',
        'code' => 500,
        'message' => 'Can not insert record to DB.'
      );
    }
  }

  public static function closeAccount($_id) {
    $accountsCollection = self::$bankDB->accounts;
    $update = array(
      'active' => false,
    );
    try {
      $accountDetail = $accountsCollection->findOne(
        [ '_id' => new MongoDB\BSON\ObjectID($_id) ]
      );
      if (!$accountDetail['active']) {
        return array(
          'status' => 'fail',
          'code' => 400,
          'message' => 'Account invalid.'
        );
      }
      $result = $accountsCollection->findOneAndUpdate(
        [ '_id' => new MongoDB\BSON\ObjectID($_id) ],
        [ '$set' => $update],
        [ 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
      );
      return array(
        'status' => 'success',
        'content' => $result
      );
    } catch(\Exception $e) {
      return array(
        'status' => 'fail',
        'code' => 400,
        'message' => 'Can not find account.'
      );
    }
  }

  public static function withdrawMoney($_id, $amount) {
    $accountsCollection = self::$bankDB->accounts;
    try {
      $accountDetail = $accountsCollection->findOne(
        [ '_id' => new MongoDB\BSON\ObjectID($_id) ]
      );
      if (!$accountDetail['active']) {
        return array(
          'status' => 'fail',
          'code' => 400,
          'message' => 'Account invalid.'
        );
      }
      $balance = floatval($accountDetail['balance']);
      if ($balance < $amount) {
        return array(
          'status' => 'fail',
          'code' => 400,
          'message' => 'Not enough balance. Remain: ' . $balance
        );
      }
      $update = array(
        'balance' => $balance - $amount
      );
      $result = $accountsCollection->findOneAndUpdate(
        [ '_id' => new MongoDB\BSON\ObjectID($_id) ],
        [ '$set' => $update],
        [ 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
      );
      return array(
        'status' => 'success',
        'content' => $result
      );
    } catch(\Exception $e) {
      return array(
        'status' => 'fail',
        'code' => 400,
        'message' => 'Can not find account.'
      );
    }
  }

  public static function depositMoney($_id, $amount) {
    $accountsCollection = self::$bankDB->accounts;
    try {
      $accountDetail = $accountsCollection->findOne(
        [ '_id' => new MongoDB\BSON\ObjectID($_id) ]
      );
      if (!$accountDetail['active']) {
        return array(
          'status' => 'fail',
          'code' => 400,
          'message' => 'Account invalid.'
        );
      }
      $balance = floatval($accountDetail['balance']);
      $update = array(
        'balance' => $balance + $amount
      );
      $result = $accountsCollection->findOneAndUpdate(
        [ '_id' => new MongoDB\BSON\ObjectID($_id) ],
        [ '$set' => $update],
        [ 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
      );
      return array(
        'status' => 'success',
        'content' => $result
      );
    } catch(\Exception $e) {
      return array(
        'status' => 'fail',
        'code' => 400,
        'message' => 'Can not find account.'
      );
    }
  }

  public static function transfer($fromAccountId, $toAccountId, $amount) {
    $accountsCollection = self::$bankDB->accounts;
    try {
      $fromAccountDetail = $accountsCollection->findOne(
        [ '_id' => new MongoDB\BSON\ObjectID($fromAccountId) ]
      );
      if (!$fromAccountDetail['active']) {
        return array(
          'status' => 'fail',
          'code' => 400,
          'message' => 'Account invalid.'
        );
      }
      $fromAccountBalance = floatval($fromAccountDetail['balance']);
      if ($fromAccountBalance < $amount) {
        return array(
          'status' => 'fail',
          'code' => 400,
          'message' => 'Not enough balance. Remain: ' . $fromAccountBalance
        );
      }
      $toAccountDetail = $accountsCollection->findOne(
        [ '_id' => new MongoDB\BSON\ObjectID($toAccountId) ]
      );
      if (!$toAccountDetail['active']) {
        return array(
          'status' => 'fail',
          'code' => 400,
          'message' => 'Receiver account invalid.'
        );
      }
      // if 2 owners is the same person
      if ($fromAccountDetail['ownerId'] == $toAccountDetail['ownerId']) {
        $fromAcoountUpdate = array(
          'balance' => $fromAccountBalance - $amount
        );
        $toAcoountUpdate = array(
          'balance' => $toAccountDetail['balance'] + $amount
        );
        $result = $accountsCollection->findOneAndUpdate(
          [ '_id' => new MongoDB\BSON\ObjectID($fromAccountId) ],
          [ '$set' => $fromAcoountUpdate],
          [ 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
        );
        $accountsCollection->findOneAndUpdate(
          [ '_id' => new MongoDB\BSON\ObjectID($toAccountId) ],
          [ '$set' => $toAcoountUpdate],
          [ 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
        );
        return array(
          'status' => 'success',
          'content' => $result
        );
      } else {
        if ($fromAccountBalance - 100.0 < $amount) {
          return array(
            'status' => 'fail',
            'code' => 400,
            'message' => 'Not enough balance. Remain: ' . $fromAccountBalance
          );
        }
        // if the 2 owners is not the same person
        $r = new \Comodojo\Httprequest\Httprequest('http://handy.travel/test/success.json');
        try {
            $r->send();
            if ($r->getHttpStatusCode() == 200) {
              $fromAcoountUpdate = array(
                'balance' => $fromAccountBalance - $amount - 100
              );
              $toAcoountUpdate = array(
                'balance' => $toAccountDetail['balance'] + $amount
              );
              $result = $accountsCollection->findOneAndUpdate(
                [ '_id' => new MongoDB\BSON\ObjectID($fromAccountId) ],
                [ '$set' => $fromAcoountUpdate],
                [ 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
              );
              $accountsCollection->findOneAndUpdate(
                [ '_id' => new MongoDB\BSON\ObjectID($toAccountId) ],
                [ '$set' => $toAcoountUpdate],
                [ 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
              );
              return array(
                'status' => 'success',
                'content' => $result
              );
            }
        } catch (HttpException $ex) {
          return array(
            'status' => 'fail',
            'code' => 500,
            'message' => 'Can not connect to tansfer approving server.'
          );
        }
      }
    } catch(\Exception $e) {
      return array(
        'status' => 'fail',
        'code' => 400,
        'message' => 'Can not find account.'
      );
    }
  }

}
