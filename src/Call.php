<?php
/**
 * Created by PhpStorm.
 * User: enno
 * Date: 18.07.17
 * Time: 17:24
 */

namespace Drupal\cmrf_core;

use CMRF\Core\AbstractCall;
use CMRF\Core\Call as CallInterface;

class Call extends AbstractCall{
  public $record   = NULL;
  public $request  = NULL;
  public $reply    = NULL;
  public static function createNew($connector_id, $core, $entity, $action, $parameters, $options, $callback,$factory) {
    $call = new Call($core, $connector_id,$factory);
    // compile request
    $call->request = $call->compileRequest($parameters, $options);
    $call->request['entity'] = $entity;
    $call->request['action'] = $action;
    // create DB entry
    $call->record = array(
      'status'       => CallInterface::STATUS_INIT,
      'connector_id' => $call->getConnectorID(),
      'request'      => json_encode($call->request),
      'metadata'     => '{}',
      'request_hash' => $call->getHash(),
      'create_date'  => date('YmdHis'),
    );
    // set the caching flag
    if (!empty($options['cache'])) {
      $call->record['cached_until'] = date('YmdHis', strtotime("now +" . $options['cache']));
    }
    return $call;
  }
  public static function createWithRecord($connector_id, $core, $record,$factory) {
    $call = new Call($core, $connector_id, $factory, $record->cid);
    $call->record  = json_decode(json_encode($record), TRUE);
    $call->request = json_decode($call->record['request'], TRUE);
    $call->reply   = json_decode($call->record['reply'], TRUE);
    return $call;
  }

  public function setReply($data, $newstatus = CallInterface::STATUS_DONE) {
    // update the DB
    $update = array(
      'cid'        => $this->id,
      'status'     => $newstatus,
      'reply_date' => date('YmdHis'),
      'reply'      => json_encode($data));
    $this->factory->update($this);
    // update the cached data
    $this->reply = $data;
    $this->record['status'] = $newstatus;
  }
  public function getEntity() {
    return $this->request['entity'];
  }
  public function getAction() {
    return $this->request['action'];
  }
  public function getParameters() {
    return $this->extractParameters($this->request);
  }
  public function getOptions() {
    return $this->extractOptions($this->request);
  }
  public function getStatus() {
    return $this->record['status'];
  }
  public function getStats() {
    return $this->record['metadata'];
  }
  public function getRequest() {
    return $this->request;
  }
  public function getReply() {
    return $this->reply;
  }
  public function triggerCallback() {
    // TODO:
  }
  public function setStatus($status, $error_message, $error_code = NULL) {
    $error = array(
      'is_error'      => '1',
      'error_message' => $error_message,
      'error_code'    => $error_code);
    // update the DB
    $update = array(
      'cid'        => $this->id,
      'status'     => $status,
      'reply_date' => date('YmdHis'),
      'reply'      => json_encode($error));
    $this->factory->update($this);
  }
}
