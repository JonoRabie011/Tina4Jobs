<?php
class FailedJob extends \Tina4\ORM
{
    public $tableName="failed_jobs";
    public $primaryKey="id"; //set for primary key
    //public $fieldMapping = ["id" => "id","uuid" => "uuid","connection" => "connection","queue" => "queue","payload" => "payload","exception" => "exception","failedAt" => "failed_at"];
    public $genPrimaryKey=true; //set to true if you want to set the primary key
    //public $ignoreFields = []; //fields to ignore in CRUD
    //public $softDelete=true; //uncomment for soft deletes in crud 
    
	public $id;
	public $uuid;
	public $connection;
	public $queue;
	public $payload;
	public $exception;
	public $failedAt;
}