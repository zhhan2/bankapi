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

$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
// request validator for /aacount/create
$accountCreateValidator = array(
  'owner' => v::alnum()
);

$app->post('/account/create', function ($request, $response, $args) {
  if($request->getAttribute('has_errors')){
    $errors = $request->getAttribute('errors');
    return $response->withStatus(500)->getBody()->write($errors->toJson());
  } else {
    $data = $request->getParsedBody();
    $accountid = MongoHelper::getInstance()->createAccount($data['name'], $data['id'], 0.0);
    return writeSuccess($response, array(
      'owner' => $data['name'],
      'accountId' => $accountid
    ));
  }
});

$app->post('/account/close', function ($request, $response, $args) {
  if($request->getAttribute('has_errors')){
    $errors = $request->getAttribute('errors');
    return $response->withStatus(500)->getBody()->write($errors->toJson());
  } else {
    $data = $request->getParsedBody();
    $accountId = $data['accountId'];
    $result = MongoHelper::getInstance()->closeAccount($accountId);
    return writeSuccess($response, array(
      'result' => $result
    ));
  }
});

$app->post('/account/withdraw', function ($request, $response, $args) {
  if($request->getAttribute('has_errors')){
    $errors = $request->getAttribute('errors');
    return $response->withStatus(500)->getBody()->write($errors->toJson());
  } else {
    $data = $request->getParsedBody();
    $accountId = $data['accountId'];
    $amount = floatval($data['amount']);
    $result = MongoHelper::getInstance()->withdrawMoney($accountId, $amount);
    if ($result['status'] == 'success') {
      return writeSuccess($response, $result['content']);
    } else {
      return writeFail($response, 402, $result['message']);
    }
  }
});

$app->post('/account/deposit', function ($request, $response, $args) {
  if($request->getAttribute('has_errors')){
    $errors = $request->getAttribute('errors');
    return $response->withStatus(500)->getBody()->write($errors->toJson());
  } else {
    $data = $request->getParsedBody();
    $accountId = $data['accountId'];
    $amount = floatval($data['amount']);
    $result = MongoHelper::getInstance()->depositMoney($accountId, $amount);
    if ($result['status'] == 'success') {
      return writeSuccess($response, $result['content']);
    } else {
      return writeFail($response, 402, $result['message']);
    }
  }
});

// $r = new HttpRequest('http://example.com/feed.rss', HttpRequest::METH_GET);
// $r->setOptions(array('lastmodified' => filemtime('local.rss')));
// $r->addQueryData(array('category' => 3));
// try {
//     $r->send();
//     if ($r->getResponseCode() == 200) {
//         file_put_contents('local.rss', $r->getResponseBody());
//     }
// } catch (HttpException $ex) {
//     echo $ex;
// }
//

$app->post('/account/transfer', function ($request, $response, $args) {
  if($request->getAttribute('has_errors')){
    $errors = $request->getAttribute('errors');
    return $response->withStatus(500)->getBody()->write($errors->toJson());
  } else {
    $data = $request->getParsedBody();
    $fromAccountId = $data['$fromAccountId'];
    $toAccountId = $data['$toAccountId'];
    $amount = floatval($data['amount']);
    $result = MongoHelper::getInstance()->transfer($fromAccountId, $toAccountId, $amount);
    if ($result['status'] == 'success') {
      return writeSuccess($response, $result['content']);
    } else {
      return writeFail($response, 402, $result['message']);
    }
  }
});
