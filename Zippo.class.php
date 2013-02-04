<?php
/**
 * Zippo is a simple class to extract, create and download a zip file.
 *
 * @author Sven Lehmann <Sven.Lehmann@gmx-topmail.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link https://github.com/Zupfel/Zippo
 * @version 1.0.0
 * @php >= 5.2.0
 */
class Zippo {
	/**
     * Compression Options
     *
     * @var array $options
     */
	protected $_options = array(
		// Archive to use
		'ARCHIVE'	=> NULL,
		// Target to write the files
		'TARGET'	=> NULL,
		// Path to the files
		'ROOT'		=> NULL,
		// Name of the zip file
		'ZIP_NAME'	=> NULL,
		// Ignore this files and dirs
		'NOT_ADD'	=> array('.','..'),
		// clean and check a dir name
		'DIR'		=> NULL,
	);

	/**
	 * Array with valide files and dirs
	 *
	 * @var array $files
	 */
	protected $files = array();

	/**
	 * @const string DS
	 */
	const DS = DIRECTORY_SEPARATOR;

	/**
     * Class constructor
     */
	public function __construct()
	{
		// Checking "zip" extension is available
		if(!extension_loaded('zip')) {
            throw new Exception('This class needs the zip extension.');
        }
		// Checking "spl" classes exists
		if(!class_exists('RecursiveDirectoryIterator', FALSE) && !class_exists('RecursiveIteratorIterator', FALSE)) {
			throw new Exception('This class needs the spl classes "RecursiveDirectoryIterator" and "RecursiveIteratorIterator".');
		}
	}

	/**
	 * Sets multi options
	 *
	 * @param array $arr
	 */
	public function setOptions(array $arr)
	{
		foreach($arr AS $name => $value) {
			$this->setOption($name, $value);
		}
	}

	/**
	 * Sets the option
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setOption($name, $value)
	{
		$name = $this->getOptionName($name);

		switch($name) {
			case 'ARCHIVE':
				$value = str_replace(array('/', '\\'), self::DS, (string) $value);
				$value = trim($value, self::DS);

				if(empty($value)) {
					throw new Exception('The value from "' . $name . '" is empty.');
				}
				$value .= self::DS;
			break;

			case 'ROOT':
			case 'TARGET':
			case 'DIR':
				$value = (string) $value;

				if(!is_dir($value)) {
					$value = dirname($value);
				}

				$value = str_replace(array('/', '\\'), self::DS, realpath($value));
				$value = rtrim($value, self::DS) . self::DS;

				if(!is_dir($value) || !file_exists($value)) {
					throw new Exception('The directory "' . $name . '" does not exist.');
				}
			break;

			case 'ZIP_NAME':
				$value = trim(str_replace(array('/', '\\'), '', (string) $value));

				if(!empty($value) && !$this->is_zip($value)) {
					$value .= '.zip';
				}
			break;

			case 'NOT_ADD':
				if(empty($value)) {
					$value = array('.','..');
				}
				else {
					if(!is_array($value)) {
						throw new Exception('The option "' . $name . '" is not a array.');
					}

					$value[] = '.';
					$value[] = '..';

					array_unique($value);
				}
			break;
		}

		if(empty($value)) {
			throw new Exception('The value from "' . $name . '" is empty.');
		}

		$this->_options[$name] = $value;
	}

	/**
	 * Returns the option value
	 *
	 * @param string $name
	 */
	protected function getOption($name)
	{
		return $this->_options[$this->getOptionName($name)];
	}

	/**
	 * Returns the option name or a exception when the option not exist
	 *
	 * @param string $name
	 */
	protected function getOptionName($name)
	{
		$name = strToUpper((string) $name);

		if(!array_key_exists($name, $this->_options)) {
			throw new Exception('The option "' . $name . '" was not found.');
		}

		return $name;
	}

	/**
	 * Add a file or dir
	 * Ignored all files and directories from the NOT_ADD array
	 *
	 * @param string $file
	 */
	public function addFile($file)
	{
		if(!is_string($file)) {
			throw new Exception('Zippo::addFile() accept only a string for $file.');
		}

		if(is_dir($file)) {
			$this->setOption('DIR', $dir);
			$file = $this->getOption('DIR');
		}
		elseif(!is_file($file)) {
			throw new Exception('Wrong, "' . $file . '" is not a file.');
		}
		if(!is_readable($file)) {
			throw new Exception('Wrong, "' . $file . '" is not readable.');
		}

		if(!in_array(basename($file), $this->getOption('NOT_ADD'))) {
			$this->_files[] = $file;
		}
	}

	/**
	 * Reads in a recursive loop a directory and add all files and subdirectories
	 * Ignored all files and directories from the NOT_ADD array
	 *
	 * @param string $dir
	 */
	public function addDir($dir)
	{
		if(!is_string($dir)) {
			throw new Exception('Zippo::addDir() accept only a string for $dir.');
		}

		$this->setOption('DIR', $dir);
		$dir	= $this->getOption('DIR');
		$ignore	= $this->getOption('NOT_ADD');

		if(!is_readable($dir)) {
			throw new Exception('Wrong, "' . $dir . '" is not readable.');
		}

		$directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), TRUE);

		foreach($directoryIterator AS $value) {
			if(!in_array($value->getFilename(), $ignore)) {
				if(($value->isDir() || $value->isFile()) && $value->isReadable()) {
					$this->_files[] = $value->getPathname();
				}
			}
		}
	}

	/**
	 * Compresses the files
	 *
	 * @param bool $folder Preserve the folder structure
	 * @return bool Returns TRUE on success or FALSE on failure
	 */
	public function compress($folder = False)
	{
		$zip		= new ZipArchive();
		$files		= $this->_files;
		$root		= $this->getOption('ROOT');
		$zipName	= $this->getOption('ZIP_NAME');
		$archive	= $this->getOption('ARCHIVE');

		if(!is_readable($root)) {
			throw new Exception('The ROOT "' . $root . '" is not readable.');
		}
		if(empty($files)) {
			throw new Exception('Zippo::compress() can\'t create a empty Zip file.');
		}

		if($zip->open($root . $zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
			throw new Exception('Zippo::compress() can\'t create a zip file.');
		}

		$root = rtrim(str_replace(array('/', '\\'), self::DS, $_SERVER['DOCUMENT_ROOT']), self::DS) . self::DS;

		foreach($files AS $file) {
			if(!is_string($file)) {
				throw new Exception('Zippo::compress() accept only a string for $file.');
			}

			$newFileName = $archive;
			$newFileName .= $folder ? str_replace($root, '', $file) : basename($file);

			if($folder && is_dir($file) && !$zip->addEmptyDir($newFileName)) {
				throw new Exception('Zippo::compress() can\'t add the dir "' . $file . '".');
			}
			elseif(is_file($file) && !$zip->addFile($file, $newFileName)) {
				throw new Exception('Zippo::compress() can\'t add the file "' . $file . '".');
			}
		}

		$this->_files = array();

		return $zip->close();
	}

	/**
	 * Decompresses the files
	 *
	 * @return bool Returns TRUE on success or FALSE on failure
	 */
	public function decompress()
	{
		$root		= $this->getOption('ROOT');
		$zipName	= $this->getOption('ZIP_NAME');
		$zipPath	= $root . $zipName;

		if(!is_readable($root)) {
			throw new Exception('The ROOT "' . $root . '" is not readable.');
		}
		if(!$this->is_zip($zipPath) || !file_exists($zipPath)) {
			throw new Exception('The zip file "' . $zipName . '" does not exist.');
		}

		$extractTo = $this->getOption('TARGET');
		if(!is_writable($extractTo)) {
			throw new Exception('Wrong, "' . $extractTo . '" is not writable.');
		}

		$zip = new ZipArchive;

		return	   $zip->open($zipPath) === TRUE
				&& $zip->extractTo($extractTo)
				&& $zip->close();
	}

	/**
	 * Download the zip file
	 *
	 * @param string $contentType
	 * @return bool Returns TRUE on success or FALSE on failure
	 */
	public function send($contentType = 'application/zip')
	{
		if(!is_string($contentType)) {
			throw new Exception('Zippo::send() accept only a string for $contentType.');
		}
		if(headers_sent()) {
			throw new Exception('Zippo::send() can\'t send more headers.');
		}

		$root		= $this->getOption('ROOT');
		$zipName	= $this->getOption('ZIP_NAME');
		$zipPath	= $root . $zipName;

		if(!is_readable($root)) {
			throw new Exception('The ROOT "' . $root . '" is not readable.');
		}
		if(!$this->is_zip($zipPath) || !file_exists($zipPath)) {
			throw new Exception('The zip file "' . $zipName . '" does not exist.');
		}

		$fileSize = filesize($zipPath);

		header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', filemtime($zipPath)));
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Accept-Ranges: bytes');
		header('Connection: close');
		header('Content-Type: ' . $contentType);
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename="' . basename($zipPath) . '";');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . $fileSize);

		return $fileSize == readfile($zipPath);
	}

	/**
	 * Tells whether the filename is a regular zip file
	 *
	 * @param string $fileName
	 * @return bool Returns TRUE on success or FALSE on failure
	 */
	protected function is_zip($fileName)
	{
		if(!is_string($fileName)) {
			throw new Exception('Zippo::is_zip() accept only a string for $fileName.');
		}

		return is_file($fileName) && strToLower(substr(basename($fileName), -4)) === '.zip';
	}

	/**
	 * Deletes a zip file
	 *
	 * @return bool Returns TRUE on success or FALSE on failure
	 */
	public function remove()
	{
		$root		= $this->getOption('ROOT');
		$zipName	= $this->getOption('ZIP_NAME');
		$zipPath	= $root . $zipName;

		if(!is_readable($root)) {
			throw new Exception('The ROOT "' . $root . '" is not readable.');
		}
		if(!$this->is_zip($zipPath) || !file_exists($zipPath)) {
			throw new Exception('The zip file "' . $zipName . '" does not exist.');
		}

		return unlink($zipPath);
	}
}