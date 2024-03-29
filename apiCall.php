<?php
/**
 * pr0gramm-apiCall Funktion
 * 
 * Eine Funktion, die sich automatisch auf pr0gramm.com einloggt
 * um mit den generierten Sessioncookies API Anfragen durchzuführen.
 * 
 * @author    RundesBalli <webspam@rundesballi.com>
 * @copyright 2022 RundesBalli
 * @version   3.0
 * @license   MIT-License
 * @see       https://github.com/RundesBalli/pr0gramm-apiCall
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
 * @param string  $authToken  Das AuthToken für oAuth, welches als Header
 *                            gesendet wird.
 * 
 * @return array  Die API-Response als assoziatives Array.
 */
function apiCall($url, $postData = NULL, $authToken = NULL) {
  /**
   * Globale Variablen aus der Konfigurationsdatei in die Funktion einbinden.
   */
  global $bindTo;
  global $userAgent;
  global $cookieFile;
  
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
    CURLOPT_USERAGENT => $userAgent,
    CURLOPT_INTERFACE => $bindTo,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10
  );
  
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
   * pr0Auth Anfrage an die API mit Token
   * (nur für oAuth)
   * 
   * Wenn kein authToken übergeben wurde, dann wird der Cookie eingebunden
   */
  if($authToken !== NULL) {
    $options[CURLOPT_HTTPHEADER] = array("pr0-api-key: ".$authToken);
    $accept403 = TRUE;
  } else {
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $accept403 = FALSE;
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
      die("cURL - Zu viele Versuche - url: ".$url."\n");
    }

    /**
     * Ausführen des cURLs und speichern der Antwort, sowie eventuell
     * anfallender Fehler.
     */
    $response = curl_exec($ch);
    $errNo = curl_errno($ch);
    $errStr = curl_error($ch);
    if($errNo != 0) {
      die("cURL - errno: ".$errNo." - errstr: ".$errStr." - url: ".$url."\n");
    }

    /**
     * Auswerten des HTTP Codes.
     */
    $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if($httpCode == 503) {
      /**
       * Zu viele Anfragen in zu kurzer Zeit.
       * 500ms warten, dann nächster Versuch (max. 10 retrys).
       */
      usleep(500000);
      continue;
    } elseif($httpCode == 200 OR $httpCode == 404) {
      /**
       * Ein nicht mehr vorhandener User wirft einen 404 zurück. Die Anfrage zeigt
       * dann in der zurückgegebenen Fehlermeldung, dass der User nicht mehr existiert.
       * Daher ist auch ein 404 eine "erfolgreiche" Anfrage.
       */
      $success = TRUE;
    } elseif($httpCode == 400) {
      /**
       * Wenn man keinen Bot-Account hat und versucht sich einzuloggen, wirft die API ohne
       * übergebene Captcha Daten einen 400 Bad Request. Hier wird drauf hingewiesen, dass
       * die Login Credentials nicht ohne Captcha übergeben werden können.
       */
      die("Login - Captcha Login erforderlich. Bitte captchaLogin.php ausführen.\n");
    } else {
      if($httpCode == 403 AND $accept403 == TRUE) {
        /**
         * Die einzige Situation wo ein HTTP403 zulässig wäre, ist bei der
         * Abfrage mit authToken. Wenn mit einem ungültigen authToken angefragt
         * wird, dann wirft die API einen 403 zurück, der dann im jeweiligen
         * Script abgefangen werden muss.
         */
        return NULL;
      } else {
        /**
         * Alle anderen Fehlermeldungen werden direkt ausgegeben und der
         * Scriptablauf wird abgebrochen.
         */
        die("cURL - httpcode: ".$httpCode." - url: ".$url."\n");
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
 * Wenn das Script per captchaLogin.php aufgerufen wird, ist die Prüfung ob ein
 * Login vorliegt irrelevant.
 */
if(!isset($captchaLogin)) {
  /**
   * Einmalige Prüfung beim Einbinden der Funktion, ob man eingeloggt ist oder
   * nicht. Falls nicht wird versucht sich automatisch einzuloggen. (Nur mit
   * Bot-Account möglich! Nicht-Bot-Accounts müssen über captchaLogin.php ein-
   * geloggt werden.)
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
      /**
       * Wenn kein Ban vorliegt, dann kann es einerseits an einem falschen
       * Passwort, oder daran liegen, dass es sich nicht um einen Bot-Account
       * handelt.
       */
      if($login['ban'] === NULL) {
        if($login['error'] == 'invalidLogin') {
          /**
           * Falsches Passwort
           */
          die("Login - falsches Passwort\n");
        } else {
          /**
           * Unbekannter Fehler mit Ausgabe der Response
           */
          die("Login - unbekannter Fehler\n".json_encode($login)."\n");
        }
      } else {
        /**
         * Account gesperrt
         */
        die("Login - der Account ist gesperrt\n");
      }
    }
  }

  /**
   * Speicherung der nonce in eine Variable zur weiteren Benutzung.
   */
  $fp = fopen($cookieFile, "r");
  $read = fread($fp, filesize($cookieFile));
  $regex = '/^(?!#)pr0gramm\.com\t.*\tme\t(.*)/m';
  preg_match_all($regex, $read, $matches);
  $nonce = substr(json_decode(urldecode($matches[1][0]), TRUE)['id'], 0, 16);

  /**
   * Wenn das Script hier angekommen ist, ist alles in Ordnung. Der Account ist
   * mit einer gültigen Sitzung eingeloggt, die nonce steht zur Verfügung und es
   * kann losgehen.
   */
}
?>
