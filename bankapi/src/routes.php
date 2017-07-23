<?php
// Routes
// Connect to Mongo DB
use Respect\Validation\Validator as v;
// Register mongo helper
require __DIR__ . '/../src/MongoHelper.php';

// Helper function to send success response
function writeSuccess($response, $content) {
  return $response->withStatus(200)->getBody()->write(json_encode(array(
    'status' => 'success',
    'content' => $content
  )));
};
// Helper function to send fail response
function writeFail($response, $status, $message) {
  return $response->withStatus($status)->getBody()->write(json_encode(array(
    'status' => 'fail',
    'message' => $message
  )));
};
// API to create account, method POST
// Required key: name: String, id: String
$app->post('/account/create', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $dataValidator = v::key('name', v::stringType()->length(1,32))
                    ->key('id', v::stringType()->length(1,32));
    if ($dataValidator->validate($data)) {
      $result = MongoHelper::getInstance()->createAccount($data['name'], $data['id'], 0.0);
      if ($result['status'] == 'success') {
        return writeSuccess($response, $result['content']);
      } else {
        return writeFail($response, $result['code'], $result['message']);
      }
    } else {
      return writeFail($response, 400, 'Invalid input Need valid attributes '.
            '*name*: String, *id*: String.');
    }
});
// API to close account, method POST
// Required key: accountId: String
$app->post('/account/close', function ($request, $response, $args) {
  $data = $request->getParsedBody();
  $dataValidator = v::key('accountId', v::stringType());
  if ($dataValidator->validate($data)) {
    $accountId = $data['accountId'];
    $result = MongoHelper::getInstance()->closeAccount($accountId);
    if ($result['status'] == 'success') {
      return writeSuccess($response, $result['content']);
    } else {
      return writeFail($response, $result['code'], $result['message']);
    }
  } else {
    return writeFail($response, 400, 'Invalid input Need valid attribute '.
          '*accountId*: String.');
  }
});
// API to withdraw money from an account, method POST
// Required key: accountId: String, amount: Number (Positive)
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
      return writeFail($response, $result['code'], $result['message']);
    }
  } else {
    return writeFail($response, 400, 'Invalid input Need valid attributes '.
          '*accountId*: String, *amount*: Positive number.');
  }
});
// API to deposit money into an account, method POST
// Required key: accountId: String, amount: Number (Positive)
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
      return writeFail($response, $result['code'], $result['message']);
    }
  } else {
    return writeFail($response, 400, 'Invalid input Need valid attributes '.
          '*accountId*: String, *amount*: Positive number.');
  }
});
// API to transfer money to another account, method POST
// Required key: fromAccountId: String, toAccountId: String, amount: Number (Positive)
$app->post('/account/transfer', function ($request, $response, $args) {
  $data = $request->getParsedBody();
  $dataValidator = v::key('fromAccountId', v::stringType())
                  ->key('toAccountId', v::stringType())
                  ->key('toAccountId', v::not(v::equals($data['fromAccountId'])))
                  ->key('amount', v::Numeric()->positive());
  if ($dataValidator->validate($data)) {
    $fromAccountId = $data['fromAccountId'];
    $toAccountId = $data['toAccountId'];
    $amount = floatval($data['amount']);
    $result = MongoHelper::getInstance()->transfer($fromAccountId, $toAccountId, $amount);
    if ($result['status'] == 'success') {
      return writeSuccess($response, $result['content']);
    } else {
      return writeFail($response, $result['code'], $result['message']);
    }
  } else {
    return writeFail($response, 400, 'Invalid input Need valid attributes '
          .'*fromAccountId*: String, *toAccountId*: String ,*amount*: '.
          'Positive number. Can not transfer to your own account');
  }
});
