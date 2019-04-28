# pr0gramm-apiCall
PHP Funktion um die API von pr0gramm.com zu benutzen.

## Grundlegendes
Die Funktionsdatei muss per `require_once("/pfad/zur/apiCall.php");` in das PHP Script eingebunden werden.
Die `apiCall.php` und die `config.php` müssen im selben Verzeichnis liegen. Falls die PHP Dateien auf einem öffentlichen Webserver liegen, so ist darauf zu achten, dass die Datei `cookies.txt` nicht eingelesen werden kann (z.B. durch Unterbindung in der `.htaccess` Datei).

## Die Funktion als solches
Das Script loggt sich mit den in der `config.php` angegebenen Nutzerdaten auf [pr0gramm.com](https://pr0gramm.com) ein und speichert die Sitzung.
Die Gültigkeit der Sitzung wird bei jedem Einbinden der Datei einmalig überprüft. Wenn die Funktion 100x innerhalb eines Scriptaufrufs genutzt wird, so wird nur einmal der Login überprüft.

## Nonce
Für manche Funktionen von [pr0gramm.com](https://pr0gramm.com) (zum Beispiel das Versenden von PNs) ist die sogenannte Nonce erforderlich. Sie kann über die Variable `$nonce` in die Postdaten mit eingefügt werden.

## Einrichtung
Die `config.template.php` muss in `config.php` umbenannt oder kopiert werden. Die darinliegenden Variablen müssen angepasst werden und sind entsprechend kommentiert.

## Nutzung
Die Parameter der Funktion sind in der `apiCall.php` ausführlich beschrieben.