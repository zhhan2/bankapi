<?php
// Routes
// Connect to Mongo DB
use Respect\Validation\Validator as v;
// Register mongo helper
require __DIR__ . '/../src/MongoHelper.php';

function writeSuccess($response, $content) {
  return $response->withStatus(200)->getBody()->write(json_encode(array(
    'status' => 'success',
    'content' => $content
  )));
};

function writeFail($response, $status, $message) {
  return $response->withStatus($status)->getBody()->write(json_encode(array(
    'status' => 'fail',
    'message' => $message
  )));
};

$app->post('/account/create', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $dataValidator = v::key('name', v::stringType()->length(1,32))
                    ->key('id', v::stringType()->length(1,32));
    if ($dataValidator->validate($data)) {
      $accountid = MongoHelper::getInstance()->createAccount($data['name'], $data['id'], 0.0);
      return writeSuccess($response, array(
        'owner' => $data['name'],
        'accountId' => $accountid
      ));
    } else {
      return writeFail($response, 400, 'Invalid input Need valid attributes *name*: String, *id*: String.');
    }
});

$app->post('/account/close', function ($request, $response, $args) {
  $data = $request->getParsedBody();
  $dataValidator = v::key('accountId', v::stringType());
  if ($dataValidator->validate($data)) {
    $accountId = $data['accountId'];
    $result = MongoHelper::getInstance()->closeAccount($accountId);
    return writeSuccess($response, array(
      'result' => $result
    ));
  } else {
    return writeFail($response, 400, 'Invalid input Need valid attribute *accountId*: String.');
  }
});

$app->post('/account/withdraw', function ($request, $response, $args) {
  $data = $request->getParsedBody();
  $dataValidator = v::key('accountId', v::stringType())
                  ->key('amount', v::Numeric()->positive());
  if ($dataValidator->validate($data)) {
    $accountId = $data['accountId'];
    $amount = floatval($data['amount']);
    $result = MongoHelper::getInstance()->withdrawMoney($accountId, $amount);
    if ($result['status'] == 'success') {
      return writeSuccess($response, $result['content']);
    } else {
      return writeFail($response, 402, $result['message']);
    }
  } else {
    return writeFail($response, 400, 'Invalid input Need valid attributes *accountId*: String, *amount*: Positive number.');
  }
});

$app->post('/account/deposit', function ($request, $response, $args) {
  $data = $request->getParsedBody();
  $dataValidator = v::key('accountId', v::stringType())
                  ->key('amount', v::Numeric()->positive());
  if ($dataValidator->validate($data)) {
    $accountId = $data['accountId'];
    $amount = floatval($data['amount']);
    $result = MongoHelper::getInstance()->depositMoney($accountId, $amount);
    if ($result['status'] == 'success') {
      return writeSuccess($response, $result['content']);
    } else {
      return writeFail($response, 402, $result['message']);
    }
  } else {
    return writeFail($response, 400, 'Invalid input Need valid attributes *accountId*: String, *amount*: Positive number.');
  }
});

$app->post('/account/transfer', function ($request, $response, $args) {
  $data = $request->getParsedBody();
  $dataValidator = v::key('fromAccountId', v::stringType())
                  ->key('toAccountId', v::stringType())
                  ->key('amount', v::Numeric()->positive());
  if ($dataValidator->validate($data)) {
    $fromAccountId = $data['fromAccountId'];
    $toAccountId = $data['toAccountId'];
    $amount = floatval($data['amount']);
    $result = MongoHelper::getInstance()->transfer($fromAccountId, $toAccountId, $amount);
    if ($result['status'] == 'success') {
      return writeSuccess($response, $result['content']);
    } else {
      return writeFail($response, 402, $result['message']);
    }
  } else {
    return writeFail($response, 400, 'Invalid input Need valid attributes *fromAccountId*: String, *toAccountId*: String ,*amount*: Positive number.');
  }
});
