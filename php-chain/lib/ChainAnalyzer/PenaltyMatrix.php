<?php


namespace PhpChain\ChainAnalyzer;


/**
 * Class PenaltyMatrix
 * @package PhpChain\ChainAnalyzer
 */
class PenaltyMatrix
{
    /**
     *
     */
    const PROPERTY = 0.99;
    /**
     *
     */
    const CONSTANT = 0.2;
    /**
     *
     */
    const CONSTANT_FOR_CONDITION = 0.9;
    /**
     *
     */
    const BASE_SANITIZER_PENALTY = 0.1;
    /**
     *
     */
    const PENALTY_FOR_OP = 0.99;
    /**
     *
     */
    const UNDER_CONDITION_BLOCK = 0.7;
    /**
     *
     */
    const FUNC_PARAM = 0.95;
    /**
     *
     */
    const SPECIFIC_SANITIZER_PENALTY = 0.01;
    /**
     *
     */
    const CONDITION_LEVEL_THRESHOLD = 0.5;
    /**
     *
     */
    const PROPERTY_LEVEL_THRESHOLD = 0.7;
    /**
     *
     */
    const UNSET_PENALTY = 1.0;
    /**
     *
     */
    const UNDEFINED_PENALTY = 0.5;
    /**
     *
     */
    const TYPE_CHANGED = 0.1;
    /**
     *
     */
    const ARRAY_NAME_PENALTY = 0.8;
    /**
     *
     */
    const ARRAY_DIM_PENALTY = 0.2;

    /**
     * securing functions for every vulnerability
     * Todo: add desanitizers. Aka decode, decrypt
     */
    const BASE_SANITIZERS_LIST = array(
		'intval',
		'floatval',
		'doubleval',
		'filter_input',
		'urlencode',
		'rawurlencode',
		'round',
		'floor',
		'strlen',
		'strrpos',
		'strpos',
		'strftime',
		'strtotime',
		'md5',
		'md5_file',
		'sha1',
		'sha1_file',
		'crypt',
		'crc32',
		'hash',
		'mhash',
		'hash_hmac',
		'password_hash',
		'mcrypt_encrypt',
		'mcrypt_generic',
		'base64_encode',
		'ord',
		'sizeof',
		'count',
		'bin2hex',
		'levenshtein',
		'abs',
		'bindec',
		'decbin',
		'dechex',
		'decoct',
		'hexdec',
		'rand',
		'max',
		'min',
		'metaphone',
		'tempnam',
		'soundex',
		'money_format',
		'number_format',
		'date_format',
		'filetype',
		'nl_langinfo',
		'bzcompress',
		'convert_uuencode',
		'gzdeflate',
		'gzencode',
		'gzcompress',
		'http_build_query',
		'lzf_compress',
		'zlib_encode',
		'imap_binary',
		'iconv_mime_encode',
		'bson_encode',
		'sqlite_udf_encode_binary',
		'session_name',
		'readlink',
		'getservbyport',
		'getprotobynumber',
		'gethostname',
		'gethostbynamel',
		'gethostbyname',
	);

    /**
     *
     */
    const HTML_SANITIZERS_LIST  = array(
        'htmlentities',
        'htmlspecialchars',
        'highlight_string',
    );

    /**
     * securing functions for SQLi
     */
    const SQL_SANITIZERS_LIST = array(
        'addslashes',
        'dbx_escape_string',
        'db2_escape_string',
        'ingres_escape_string',
        'maxdb_escape_string',
        'maxdb_real_escape_string',
        'mysql_escape_string',
        'mysql_real_escape_string',
        'mysqli_escape_string',
        'mysqli_real_escape_string',
        'pg_escape_string',
        'pg_escape_bytea',
        'sqlite_escape_string',
        'sqlite_udf_encode_binary',
        'cubrid_real_escape_string',
    );

    /**
     * securing functions for RCE with e-modifier in preg_**
     */
    const PREG_SANITIZERS_LIST = array(
        'preg_quote'
    );

    /**
     * securing functions for file handling
     */
    const FILE_SANITIZERS_LIST = array(
        'basename',
        'dirname',
        'pathinfo'
    );

    /**
     * securing functions for OS command execution
     */
    const RCE_SANITIZERS_LIST = array(
        'escapeshellarg',
        'escapeshellcmd'
    );

    /**
     * securing XPath injection
     */
    const XPATH_SANITIZERS_LIST = array(
        'addslashes'
    );

}