<?php
namespace com\yp\db;

class Result implements \JsonSerializable
{

    private $success;

    private $message;

    private $data;

    private $errorCode;
    
    private $dataLength;

    public function __construct(bool $pSuccess = false, String $pMessage = "")
    {
        $this->success = $pSuccess;
        $this->message = $pMessage;
        $this->data = null;
        $this->errorCode = 0;
        $this->dataLength = 0;
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function setSuccess(bool $pSuccess)
    {
        $this->success = $pSuccess;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(String $pMessage)
    {
        $this->message = $pMessage;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($pData)
    {
        $this->data = $pData;
    }

    public function getErrorCode():int
    {
        return $this->errorCode;
    }

    public function setErrorCode(int $pErrorCode)
    {
        $this->errorCode = $pErrorCode;
    }
    
    public function getDataLength():int
    {
        return $this->dataLength;
    }
    
    public function setDataLength(int $pDataLength)
    {
        $this->dataLength = $pDataLength;
    }

    public function jsonSerialize()
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'errorCode' => $this->errorCode,
            'dataLength' => $this->dataLength
        ];
    }
}