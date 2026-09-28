# Cloudforms 0.3.0

Ein Formular aus einer Nextcloud einbetten. Entwickelt von Liam Perlaki.

Ein Formular, das jemand in Nextcloud Forms baut, als Formular der eigenen Website: aus den Fragen
werden echte Felder, eine Antwort schickt der Webserver in die Cloud, und die Antworten bleiben,
wo sie vorher waren. Auf Papier druckt das Formular als der Zettel, den es ersetzt.

## Eine Erweiterung installieren

[ZIP-Datei herunterladen](https://github.com/pfadfinder26/yellow-cloudforms/archive/refs/heads/main.zip) und in den Ordner `system/extensions` kopieren. [Mehr über Erweiterungen](https://github.com/annaesvensson/yellow-update).

## Ein Formular einbetten

Ein Formular in Nextcloud Forms teilen, „Link kopieren“, und in eine Seite schreiben:

    [form https://cloud.example.org/apps/forms/s/TOKEN]

`CloudformsUrl` in den Systemeinstellungen hält die Cloud und ein Formular davon, eine Seite kann
also mit `[form]` allein nach dem Formular der Website fragen. Der Link muss für alle offen sein
und Antworten ohne Anmeldung annehmen.

Die Erweiterung liest die Seite des Links, die die Fragen enthält, und legt eine Kopie in `system/cache` für
`CloudformsCacheTime` Sekunden ab, eine Stunde als Vorgabe. Ein Besuch wartet nicht auf die Cloud,
und ist die Cloud nicht erreichbar, gilt die letzte Kopie.

**Abschicken:** das Formular geht an den eigenen Webserver, der die Antwort in die Cloud schickt.
Das kommt ohne JavaScript aus. Eine Anfrage von einer anderen Website wird abgelehnt, und ein Feld,
das niemand sieht, fängt die einfachsten Robots ab. Danach wird die Seite erneut gezeigt, von der es kam,
unter derselben Adresse wie vorher: was die Cloud geantwortet hat, reist in einem Cookie, das eine
Minute lebt und gelöscht wird, sobald die Seite es gesagt hat.

Eine Liste, deren Antworten alle kurz sind, trägt eine eigene Klasse, `cloudform-options-short`,
ein Theme kann „Ja“ und „Nein“ also in eine Zeile setzen.

**Fragen:** kurzer und langer Text, Datum, Uhrzeit, Datum mit Uhrzeit, Auswahl aus einer Liste,
mehrere Antworten und eine Antwort aus mehreren werden zu den Feldern, die man erwartet. Wogegen
eine kurze Antwort in der Cloud geprüft wird, kommt mit: aus einer Adresse wird ein E-Mail-Feld,
aus einer Zahl ein Zahlenfeld, aus einer Telefonnummer ein Telefonfeld, aus einem eigenen Ausdruck
das Muster des Feldes, der Browser sagt also vorher, was nicht stimmt. Trägt eine Frage einen Namen
zum automatischen Ausfüllen, wird auch der weitergegeben. Alles
andere wird eine Zeile Text. Eine Frage, die eine Datei will, sagt stattdessen, was damit zu tun ist, `CloudformsLabelFile`,
ausdrucken oder per Mail schicken, denn die Datei gehört in die Cloud und nie auf diese Website. Ist so eine Frage eine Pflichtfrage, wird das Formular gar nicht
gezeigt, nur der Link, weil eine Antwort ohne die Datei abgelehnt würde.

**Was das Formular sagt:** Titel und Beschreibung des Formulars stehen über den Feldern, die
Beschreibung einer einzelnen Frage über deren Feld. Ein Formular, das geschlossen, abgelaufen oder
voll ist, zeigt keine Felder, nur einen Hinweis und den Link in die Cloud. Hat ein Formular eine
eigene Nachricht für danach, steht sie anstelle des üblichen Dankes.

**Bestätigung per E-Mail:** Nextcloud Forms kann eine schicken, „Bestätigungsmail“ in den
Einstellungen des Formulars, zusammen mit der Frage, deren Antwort die Adresse enthält. Dafür ist
hier nichts nötig: die Antworten sind dieselben, die die Cloud sonst bekommen hätte, sie verschickt
ihre Mail also wie gewohnt. Die Frage nach der Adresse macht man am besten zur Pflichtfrage, die als
Adresse geprüft wird.

Ist eine Antwort angekommen, bietet ein Knopf an, das Formular noch einmal auszufüllen, für das
zweite Kind einer Familie.

**Beschriftungen:** `CloudformsLabelSubmit`, `CloudformsLabelDone`, `CloudformsLabelAgain`,
`CloudformsLabelFailed`, `CloudformsLabelRequired`, `CloudformsLabelClosed`, `CloudformsLabelFile`
und `CloudformsLabelOpen` sagen, was das Formular sagt, in der Sprache
der Website.

**Drucken:** das Formular ist schlichtes HTML, `.cloudform`, darin `.cloudform-question`,
`.cloudform-label` und die Felder, ein Theme kann es also als Zettel zum Ausfüllen drucken. Ein
Browser kann kein PDF mit Feldern erzeugen, in die man tippen kann, er druckt nur, was die Seite
zeigt.

**Datenschutz:** die Antworten schickt der eigene Webserver, die Besucher*innen reden also nie
selbst mit der Cloud, und die Cloud sieht ihre Adresse nicht. Der Server hebt die Antworten nicht auf.
