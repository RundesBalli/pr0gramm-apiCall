<?php
/**
 * config.php
 * 
 * Konfiguration für den pr0gramm-apiCall
 */

/**
 * Speicherort der Cookiedatei
 * 
 * @var string
 */
$cookiefile = __DIR__.DIRECTORY_SEPARATOR."cookies.txt";

/**
 * Fehleranzeige ein- oder ausschalten
 * 
 * @var boolean
 */
$showErrors = TRUE;

/**
 * Die IP-Adresse die für die ausgehende Verbindung genutzt werden soll.
 * 
 * Beispielwert: 1.2.3.4
 * 
 * @var string
 */
$bindTo = "";

/**
 * Der Useragent der gesendet wird.
 * 
 * Beispielwert: Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:66.0) Gecko/20100101 Firefox/66.0
 * oder          Heinrichs lustige Datenkrake
 * 
 * @var string
 */
$useragent = "";

/**
 * Pr0gramm Zugangsdaten
 * 
 * @var string
 * @var string
 */
$pr0Username = "";
$pr0Password = "";
?>
