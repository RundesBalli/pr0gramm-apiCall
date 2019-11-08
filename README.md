# pr0gramm-apiCall
PHP Funktion um die API von pr0gramm.com zu benutzen.
Es handelt sich hierbei nicht um eine lib, sondern um eine Standalone-Funktion um die API von [pr0gramm.com](https://pr0gramm.com) mit einem Sessioncookie zu verwenden.

## Grundlegendes
Die Funktionsdatei muss per `require_once("/pfad/zur/apiCall.php");` in das PHP Script eingebunden werden.
Die `apiCall.php` und die `config.php` müssen im selben Verzeichnis liegen. Falls die PHP Dateien auf einem öffentlichen Webserver liegen, so ist darauf zu achten, dass die Datei `cookies.txt` nicht eingelesen werden kann (z.B. durch Unterbindung in der `.htaccess` Datei).

## Der Login
### mit Bot-Account (Nutzer-Bot)
Das Script loggt sich mit den in der `config.php` angegebenen Nutzerdaten auf [pr0gramm.com](https://pr0gramm.com) ein und speichert die Sitzung.

### ohne Bot-Account (mit normalem Nutzeraccount)
Da ein Captcha erforderlich ist muss der Login händisch erfolgen. Um keine aufwändige Fummelarbeit mit der `cookies.txt` Datei zu haben, kann man das Captcha über die Datei `captchaLogin.php` lösen.  
Dazu ist es erforderlich `php captchaLogin.php` auszuführen (alternativ: Aufruf über Webbrowser, wenn kein Zugriff auf das Terminal). Alle weiteren Anweisungen gibt das Script automatisch aus.

### Die Sitzung an sich
Die Gültigkeit der Sitzung wird bei jedem Einbinden der Datei einmalig überprüft. Wenn die Funktion 100x innerhalb eines Scriptaufrufs genutzt wird, so wird nur einmal der Login überprüft.

## Nonce
Für manche Funktionen von [pr0gramm.com](https://pr0gramm.com) (zum Beispiel das Versenden von PNs) ist die sogenannte Nonce erforderlich. Sie kann über die Variable `$nonce` in die Postdaten mit eingefügt werden.

## Einrichtung
Die `config.template.php` muss in `config.php` umbenannt oder kopiert werden. Die darinliegenden Variablen müssen angepasst werden und sind entsprechend kommentiert.

## Nutzung
Die Parameter der Funktion sind in der `apiCall.php` ausführlich beschrieben. https://github.com/RundesBalli/pr0gramm-apiCall/blob/master/apiCall.php#L20

## Mögliche Probleme
Auf verschiedenen System ist es möglich dass die Ausführung von `php captchaLogin.php` mit dem Fehler `cURL - errno: 45 - errstr: bind failed with errno 97: Address family not supported by protocol - url: https://pr0gramm.com/api/user/loggedin` abgebrochen wird.
Ein einfacher Workaround ist es in der `apiCall.php` Datei die Zeile `CURLOPT_INTERFACE => $bindTo,` auszukommentieren bzw. zu entfernen.
