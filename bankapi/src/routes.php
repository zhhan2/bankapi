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
    $accountid = MongoHelper::getInstance()->createAccount($data['owner'], 0);
    return writeSuccess($response, array(
      'owner' => $data['owner'],
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
