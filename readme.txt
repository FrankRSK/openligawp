=== OpenLigaWP - Live Sportdaten ===
Contributors: frankkemper
Tags: football, soccer, sports, bundesliga, live-scores, openligadb, ajax
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Zeige Live-Fussballdaten (Bundesliga, etc.) mit Spieltagen und Tabelle auf deiner Website an.

== Description ==

OpenLigaWP ist ein leichtgewichtiges WordPress-Plugin zur Anzeige von Live-Sportdaten uber die [OpenLigaDB API](https://www.openligadb.de/). 

= Funktionen =

* **Liga-Auswahl** - Wunsche aus verschiedenen Ligen (Bundesliga, 2. Bundesliga, 3. Liga und mehr)
* **Saison-Wahl** - Wechsle zwischen aktuellen und vergangenen Saisons
* **Spieltag-Auswahl** - Navigiere durch alle Spieltage einer Saison
* **Live-Tabelle** - Zeige die aktuelle Tabelle der gewahlten Liga
* **Tor-Details** - Zeige Torschutzen und Spielminute fur jedes Spiel
* **AJAX-Loading** - Dynamisches Nachladen ohne Seitenrefresh
* **Responsive Design** - Optimiert fur Desktop und Mobile
* **Cookie-Speicherung** - Merkt sich die letzte gewahlte Liga und Saison

= Verfugbare Ligen =

Das Plugin unterstutzt alle Ligen, die uber OpenLigaDB verfugbar sind, darunter:

* 1. Bundesliga
* 2. Bundesliga  
* 3. Liga
* DFB-Pokal
* Champions League
* Und viele weitere...

= Shortcode =

Verwende den Shortcode `[olwp_dashboard]` auf einer beliebigen Seite oder Beitrag.

== Installation ==

1. Lade das Plugin-Verzeichnis `openligawp` in das Verzeichnis `/wp-content/plugins/` hoch
2. Aktiviere das Plugin im WordPress-Backend unter "Plugins"
3. Gehe zu Einstellungen > OpenLigaWP um die Ligen zu konfigurieren
4. Fuge den Shortcode `[olwp_dashboard]` auf einer Seite ein

= Manuelle Installation =

1. Entpacke das Plugin-Archiv
2. Lade den Ordner `openligawp` per FTP nach `/wp-content/plugins/` hoch
3. Aktiviere das Plugin im WordPress-Adminbereich

== Frequently Asked Questions ==

= Woher kommen die Daten? =

Die Sportdaten werden uber die kostenlose [OpenLigaDB API](https://www.openligadb.de/) abgerufen. OpenLigaDB ist ein Community-Projekt, das Fussballdaten fur Entwickler zur Verfugung stellt.

= Welche Ligen werden unterstutzt? =

Grundlegend werden alle Ligen unterstutzt, die OpenLigaDB anbietet. In den Einstellungen kannst du eigene Ligen hinzufugen. Das Format ist: `Name|Shortcut` (z.B. `1. Bundesliga|bl1`).

= Wie kann ich das Aussehen andern? =

Das Plugin enthalt eine CSS-Datei (`style.css`), die du anpassen kannst. Alternativ kannst du eigene CSS-Regeln in deinem Theme oder im WordPress Customizer hinzufugen.

= Warum werden keine Daten angezeigt? =

1. Prufe, ob die Liga in den Einstellungen korrekt konfiguriert ist
2. Prufe, ob die gewahlte Saison Daten enthalt
3. Es kann sein, dass die OpenLigaDB API temporar nicht erreichbar ist

= Ist das Plugin kostenlos? =

Ja, das Plugin ist kostenlos und steht unter der GPL v2 Lizenz.

== Screenshots ==

1. Das Dashboard mit Spieltag-Auswahl und Tabelle
2. Die Einstellungsseite zur Liga-Konfiguration
3. Responsive Ansicht auf mobilen Geraten

== Changelog ==

= 2.1 =
* Verbesserte Fehlerbehandlung
* Optimierte Caching-Strategie
* Bugfix: Falsche Spieltag-Reihenfolge korrigiert

= 2.0 =
* Komplette Uberarbeitung des Frontends
* Neue responsive Layout-Struktur
* AJAX-basiertes Laden der Daten

= 1.0 =
* Erste offentliche Version

== Upgrade Notice ==

= 2.1 =
Wichtiges Update mit Fehlerbehebungen und Performance-Verbesserungen.

== Resources ==

* [OpenLigaDB API Dokumentation](https://github.com/OpenLigaDB/OpenLigaDB-Samples)
* [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
