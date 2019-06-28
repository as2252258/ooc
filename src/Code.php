<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/27 0027
 * Time: 16:33
 */

class Code
{
	//0.未读 1.已读 2.屏蔽 3.忽略 4.删除
	const STATUS_UNKNOWN = 0;       //未处理
	const STATUS_SUCCESS = 1;       //正常
	const STATUS_AUDITING = 2;      //审核中
	const STATUS_DELETE = 3;        //已删除
	
	const DEVICE_PC = 'pc';
	const DEVICE_WEB = 'web';
	const DEVICE_WAP = 'wap';
	const DEVICE_MOBILE = ['iphone' , 'android'];
	
	const SUCCESS = 0;
	const ERROR = 1;
	const NOT_EXISTS = 100;
	const SYSTEM_ERROR = 500;
	const AUTH_ERROR = 401;
	const TOKEN_LONGER_VALID = 402;
	const AUTH_GROUP_ERROR = 403;
	const REQUEST_METHOD_ERROR = 1000;
	const SMS_TYPE_ERROR = 1001;
	const SMS_SEND_ERROR = 1002;
	const USER_STATUS_ERROR = 1003;
	const DATA_EMPTY = 1004;
	const DB_ERROR = 1005;
	const ERROR_LEVEL_TASK = 'task';
	const ERROR_LEVEL_ERROR = 'error';
	const ERROR_LEVEL_SHUTDOWN = 'shutdown';
	const ERROR_LEVEL_EXCEPTION = 'exception';
	
	public static $INFO = [
		self::SUCCESS              => 'request success' ,
		self::ERROR                => 'system error' ,
		self::SYSTEM_ERROR         => 'server error, please contact admin' ,
		self::AUTH_ERROR           => 'Authentication failed. Please try again later' ,
		self::TOKEN_LONGER_VALID   => 'Your TOKEN may no longer be valid. Please log in again' ,
		self::AUTH_GROUP_ERROR     => 'Your user group has no access rights!' ,
		self::REQUEST_METHOD_ERROR => 'Request error, please check your request method!' ,
		self::SMS_TYPE_ERROR       => 'SmsVery Type error!' ,
		self::SMS_SEND_ERROR       => 'Push SmsVery error!' ,
		self::USER_STATUS_ERROR    => 'User Status error!' ,
		self::DATA_EMPTY           => 'Data Not Exists' ,
		self::DB_ERROR             => 'Mysql Save error' ,
	];
}
