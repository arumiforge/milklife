<?php

/**
 * Bootstrap PHPStan (F7-31, K123).
 *
 * Hanya mendefinisikan konstanta jalur dan konstanta aplikasi agar PHPStan
 * mengenalinya. Framework tidak dinyalakan dan tidak ada sambungan basis data.
 * Dijalankan lewat `composer analisis`.
 */

defined('ENVIRONMENT') || define('ENVIRONMENT', 'testing');
defined('CI_DEBUG') || define('CI_DEBUG', false);

defined('HOMEPATH') || define('HOMEPATH', __DIR__ . DIRECTORY_SEPARATOR);
defined('APPPATH') || define('APPPATH', __DIR__ . '/app/');
defined('ROOTPATH') || define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
defined('SYSTEMPATH') || define('SYSTEMPATH', __DIR__ . '/vendor/codeigniter4/framework/system/');
defined('WRITEPATH') || define('WRITEPATH', __DIR__ . '/writable/');
defined('FCPATH') || define('FCPATH', __DIR__ . '/public/');
defined('PUBLICPATH') || define('PUBLICPATH', __DIR__ . '/public/');
defined('CONFIGPATH') || define('CONFIGPATH', __DIR__ . '/app/Config/');
defined('TESTPATH') || define('TESTPATH', __DIR__ . '/tests/');
defined('SUPPORTPATH') || define('SUPPORTPATH', __DIR__ . '/tests/_support/');
defined('VENDORPATH') || define('VENDORPATH', __DIR__ . '/vendor/');
defined('CIPATH') || define('CIPATH', __DIR__ . '/vendor/codeigniter4/framework/');

require_once __DIR__ . '/app/Config/Constants.php';
