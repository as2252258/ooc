<?php


namespace Yoc\http;


class File
{

	public $name = '';
	public $tmp_name = '';
	public $error = '';
	public $type = '';
	public $size = '';

	private $errorInfo = [
		0 => 'UPLOAD_ERR_OK.',
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		3 => 'The uploaded file was only partially uploaded.',
		4 => 'No file was uploaded.',
		6 => 'Missing a temporary folder.',
		7 => 'Failed to write file to disk.',
		8 => 'A PHP extension stopped the file upload.'
	];

	/**
	 * @param string $path
	 * @return bool
	 * @throws \Exception
	 */
	public function saveTo(string $path)
	{
		if ($this->hasError()) {
			throw new \Exception($this->getErrorInfo());
		}

		@move_uploaded_file($this->tmp_name, $path);
		if (!file_exists($path)) {
			return false;
		}
		return true;
	}

	/**
	 * @return string
	 */
	public function getTmpPath()
	{
		return $this->tmp_name;
	}

	/**
	 * @return bool
	 *
	 * check file have error
	 */
	public function hasError()
	{
		return $this->error !== 0;
	}

	/**
	 * @return mixed
	 *
	 * get upload error info
	 */
	public function getErrorInfo()
	{
		if (!isset($this->errorInfo[$this->error])) {
			return 'Unknown upload error.';
		}
		return $this->errorInfo[$this->error];
	}

}
