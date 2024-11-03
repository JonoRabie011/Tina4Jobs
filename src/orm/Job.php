<?php
class Job extends \Tina4\ORM
{
    public $tableName="jobs";
    public $primaryKey="id"; //set for primary key
    //public $fieldMapping = ["id" => "id","queue" => "queue","payload" => "payload","attempts" => "attempts","reservedAt" => "reserved_at","availableAt" => "available_at","createdAt" => "created_at"];
    public $genPrimaryKey=true; //set to true if you want to set the primary key
    //public $ignoreFields = []; //fields to ignore in CRUD
    //public $softDelete=true; //uncomment for soft deletes in crud 
    
	public $id;
	public $queue;
	public $payload;
	public $attempts;
	public $reservedAt;
	public $availableAt;
	public $createdAt;
}