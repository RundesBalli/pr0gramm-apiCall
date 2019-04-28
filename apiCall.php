<?php
/**
 * pr0gramm-apiCall Funktion
 * 
 * Eine Funktion, die sich automatisch auf pr0gramm.com einloggt
 * um mit den generierten Sessioncookies API Anfragen durchzuführen.
 * 
 * @author    RundesBalli <webspam@rundesballi.com>
 * @copyright 2019 RundesBalli
 * @version   2.0
 * @license   MIT-License
*/

/**
 * Einbinden der Konfigurationsdatei.
 */
require_once(__DIR__.DIRECTORY_SEPARATOR."config.php");

/**
 * pr0gramm-apiCall Funktion
 * 
 * @param string  $url        Die URL die aufgerufen werden soll.
 * @param array   $postData   Wenn Daten per POST übergeben werden sollen, dann
 *                            müssen sie als Array übergeben werden:
 *                            POSTFIELD=>POSTVALUE
 * 
 * @return array  Die API-Response als assoziatives Array.
 */
function apiCall($url, $postData = NULL) {
  /**
   * Globale Variablen aus der Konfigurationsdatei in die Funktion einbinden.
   */
  global $bindTo;
  global $useragent;
  global $showErrors;
  global $cookiefile;
  
  /**
   * cURL initialisieren
  */
  $ch = curl_init();
  
  /**
   * Verbindungsoptionen vorbereiten
   * @see https://www.php.net/manual/de/function.curl-setopt.php
   */
  $options = array(
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_URL => $url,
    CURLOPT_USERAGENT => $useragent,
    CURLOPT_INTERFACE => $bindTo,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10
  );
  
  /**
   * Einbinden der Sitzungscookies.
   */
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
  
  /**
   * Postdaten vorbereiten und mit in die Optionen einbinden, sofern durch den
   * Funktionsaufruf übergeben und gewünscht.
   */
  if($postData !== NULL AND is_array($postData)) {
    $data = http_build_query($postData, '', '&', PHP_QUERY_RFC1738);
    $options[CURLOPT_POST] = TRUE;
    $options[CURLOPT_POSTFIELDS] = $data;
  }
  
  /**
   * Das Optionsarray in den cURL-Handle einfügen
   */
  curl_setopt_array($ch, $options);
  
  /**
   * Initialisieren der Durchlaufvariablen
   */
  $try = 0;
  $success = FALSE;
  /**
   * Versuch die API zu erreichen. Falls zu viele Abfragen erfolgt sind wird
   * die Abfrage bis zu 10x wiederholt und dann abgebrochen.
   */
  do {
    $try++;
    if($try > 10) {
      if($showErrors === TRUE) {
        die("cURL - Zu viele Versuche - url: $url");
      } else {
        die();
      }
    }
    /**
     * Ausführen des cURLs und speichern der Antwort, sowie eventuell
     * anfallender Fehler.
     */
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $errstr = curl_error($ch);
    if($errno != 0) {
      if($showErrors === TRUE) {
        die("cURL - errno: $errno - errstr: $errstr - url: $url");
      } else {
        die();
      }
    }
    /**
     * Auswerten des HTTP Codes.
     * Bei HTTP503 hat man zu viele Anfragen in zu kurzer Zeit gestellt. Das
     * Script wartet dann für eine halbe Sekunde und läuft erneut durch.
     * Alles was nicht HTTP200 oder HTTP503 ist wird mit einer Fehlermeldung
     * beendet.
     * Wenn die Anfrage erfolgreich war und mit HTTP200 quittiert wird, dann
     * wird die Antwort als assoziatives Array zurückgegeben.
     */
    $http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if($http_code == 503) {
      usleep(500000);
      continue;
    } elseif($http_code == 200) {
      $success = TRUE;
    } else {
      if($showErrors === TRUE) {
        die("cURL - httpcode: $http_code - url: $url");
      } else {
        die();
      }
    }
  } while($success == FALSE);
  
  /**
   * Beenden des cURL-Handles und speichern der Antwortcookies, sofern
   * vorhanden.
   */
  curl_close($ch);
  
  /**
   * Umwandeln des JSON-Strings aus der Antwort in ein assoziatives Array.
   */
  $response = json_decode($response, TRUE);
  /**
   * Rückgabe des zuvor erzeugten assoziativen Arrays.
   */
  return $response;
}

/**
 * Einmalige Prüfung beim Einbinden der Funktion, ob man eingeloggt ist oder
 * nicht. Falls nicht wird versucht sich einzuloggen.
 */
$loggedIn = apiCall("https://pr0gramm.com/api/user/loggedin", NULL);
if($loggedIn['loggedIn'] !== TRUE) {
  /**
   * Der Login wird durchgeführt
   */
  $login = apiCall("https://pr0gramm.com/api/user/login", array("name" => $pr0Username, "password" => $pr0Password));
  /**
   * Wenn der Login nicht erfolgreich war, dann wird noch geprüft wieso dies so
   * ist. In jedem Fall wird die Ausführung unterbrochen.
   */
  if($login['success'] !== TRUE) {
    if($showErrors === FALSE) {
      die();
    } else {
      if($login['ban'] === NULL) {
        die("Login - falsches Passwort");
      } else {
        die("Login - der Account ist gesperrt");
      }
    }
  }
}

/**
 * Speicherung der nonce in eine Variable zur weiteren Benutzung.
 */
$fp = fopen($cookiefile, "r");
$read = fread($fp, filesize($cookiefile));
$regex = '/^(?!#)pr0gramm\.com\t.*\tme\t(.*)/m';
preg_match_all($regex, $read, $matches);
$nonce = substr(json_decode(urldecode($matches[1][0]), TRUE)['id'], 0, 16);

/**
 * Wenn das Script hier angekommen ist, ist alles in Ordnung. Der Account ist
 * mit einer gültigen Sitzung eingeloggt, die nonce steht zur Verfügung und es
 * kann losgehen.
 */
?>
