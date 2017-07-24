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
	// Create an account with initial values
  public static function createAccount($ownerName, $ownerId, $initialBalance) {
    $accountsCollection = self::$bankDB->accounts;
    $accountDocument = array(
      'ownerName' => $ownerName,
      'balance' => $initialBalance,
      'ownerId' => $ownerId,
      'active' => true,
			'remainTransferQuota' => 10000,
			'lastTransferDate' => ''
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
	// Close an account.
  public static function closeAccount($_id) {
    $accountsCollection = self::$bankDB->accounts;
    $update = array(
      'active' => false,
    );
    try {
      $accountDetail = $accountsCollection->findOne(
        [ '_id' => new MongoDB\BSON\ObjectID($_id) ]
      );
			// Can not close a closed account
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
	// Get the remain balance of an account
	public static function getBalance($_id) {
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
      return array(
        'status' => 'success',
        'content' => [ 'balance' => $accountDetail['balance'] ]
      );
    } catch(\Exception $e) {
      return array(
        'status' => 'fail',
        'code' => 400,
        'message' => 'Can not find account.'
      );
    }
  }
	// withdraw money from an active account
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
	// Deposite money into an active account
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
	// transfer money to another account
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
			// Check if the reamin balance is enough for the transfering
      $fromAccountBalance = floatval($fromAccountDetail['balance']);
      if ($fromAccountBalance < $amount) {
        return array(
          'status' => 'fail',
          'code' => 400,
          'message' => 'Not enough balance. Remain: ' . $fromAccountBalance
        );
      }
      $remainTransferQuota = floatval($fromAccountDetail['remainTransferQuota']);
      $lastTransferDate = $fromAccountDetail['lastTransferDate'];
			$moment = new \Moment\Moment();
			$todayDate = $moment->getYear() . $moment->getMonth() . $moment->getDay();
			// if this is not the first transfer today and the reamin transfer quota is not enough
			if (($amount > 10000)
				|| ($todayDate == $lastTransferDate && $remainTransferQuota < $amount)) {
        return array(
          'status' => 'fail',
          'code' => 400,
          'message' => 'Not enough tranfer quota for today. Remain: ' . $remainTransferQuota
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
          'balance' => $fromAccountBalance - $amount,
					'lastTransferDate' => $todayDate,
					// rewrite last transfer date and remain quota
					'remainTransferQuota' => $todayDate == $lastTransferDate
																	? $remainTransferQuota - $amount
																	: 10000 - $amount
        );
        $toAcoountUpdate = array(
          'balance' => $toAccountDetail['balance'] + $amount
        );
				// update from account
        $result = $accountsCollection->findOneAndUpdate(
          [ '_id' => new MongoDB\BSON\ObjectID($fromAccountId) ],
          [ '$set' => $fromAcoountUpdate],
          [ 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
        );
				// update to account
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
                'balance' => $fromAccountBalance - $amount - 100,
								'lastTransferDate' => $todayDate,
								// rewrite last transfer date and remain quota
								'remainTransferQuota' => $todayDate == $lastTransferDate
																				? $remainTransferQuota - $amount
																				: 10000 - $amount
              );
              $toAcoountUpdate = array(
                'balance' => $toAccountDetail['balance'] + $amount
              );
							// update from account
              $result = $accountsCollection->findOneAndUpdate(
                [ '_id' => new MongoDB\BSON\ObjectID($fromAccountId) ],
                [ '$set' => $fromAcoountUpdate],
                [ 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
              );
							// update to account
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
