<?php
require __DIR__ . '/../vendor/autoload.php';

class MongoHelper {

	private static $_instance; //The single instance
  private static $bankDB; //The single instance

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

  public static function createAccount($owner, $initialBalance) {
    $accountsCollection = self::$bankDB->accounts;
    $accountDocument = array(
      "owner" => $owner,
      "balance" => $initialBalance
    );
    $result = $accountsCollection->insertOne($accountDocument);
    return $result->getInsertedId();
  }

  public static function closeAccount($_id) {
    $accountsCollection = self::$bankDB->accounts;
    $update = array(
      'active' => false,
    );
    $result = $accountsCollection->findOneAndUpdate(
      [ '_id' => new MongoDB\BSON\ObjectID($_id) ],
      [ '$set' => $update],
      [ 'returnDocument' => MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER ]
    );
    return $result;
  }
}
