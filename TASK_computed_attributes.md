Implementiere in diesem UnoPim-Checkout (Branch `feature/calculated-attributes`,
Basis: UnoPim 3.1.3) ein neues Feature: "Berechnete Attribute" (Computed/Calculated
Attributes).

## Hintergrund / Motivation
UnoPim kennt aktuell keine Attribute, deren Wert sich automatisch aus anderen
Attributen desselben Produkts zusammensetzt. Das wird produktiv für einen CSV-Export
gebraucht, konkret z.B.:
- "Artikelfamilie": Pfad aus der Kategoriehierarchie mit "#" verkettet
  (z.B. Kategorie-Label-Pfad "Arbeitskleidung#Anzug")
- Eine Spalte, die z.B. "Hersteller + Herstellerartikelnummer" zu einem Schlüssel verkettet

Das sind nur Beispiele — das Feature soll generisch für beliebige Formeln nutzbar sein,
nicht hartcodiert für diese Fälle.

## Anforderungen

1. Neuer Attributtyp "calculated" (oder Erweiterung bestehender Attribute um ein
   optionales "Formel"-Feld), anlegbar/editierbar in der Attribut-Verwaltung
   (Catalog > Attribute).

2. Formel-Definition:
   - Referenziert andere Attribut-Codes des gleichen Produkts (inkl.
     common/locale_specific/channel_locale_specific Scope) und ggf. Systemfelder
     (sku, categories, price...).
   - Unterstützt mindestens: String-Konkatenation mit konfigurierbarem Trennzeichen,
     Bedingungen (if/else bzw. leer-wenn-leer), einfache Arithmetik, Datumsfunktionen,
     Zugriff auf Kategorie-Pfad/Label-Pfad (nicht nur Code).
   - KEINE beliebige PHP-Ausführung zulassen (Sicherheitsrisiko). Nutze eine sichere,
     sandboxed Expression-Language (z.B. symfony/expression-language oder eine selbst
     definierte, eng begrenzte DSL) mit Whitelist an Funktionen.
   - Zirkuläre Abhängigkeiten zwischen berechneten Attributen müssen beim Speichern der
     Formel erkannt und abgelehnt werden (Dependency-Graph-Check).

3. Auswertung / Caching:
   - Wert wird nicht zwingend dauerhaft in der DB gespeichert, sondern bei Bedarf
     berechnet (Produktanzeige, Export, API-Response) — ODER wahlweise materialisiert
     mit Neuberechnung bei Speichern eines abhängigen Attributs/Kategorie-Zuordnung.
     Bewerte beide Varianten technisch (Performance bei ~1500+ Produkten) in einem
     kurzen Design-Dokument, bevor du dich entscheidest, und dokumentiere die
     Begründung.
   - Muss beim CSV/XLSX-Export (Webkul\DataTransfer\Helpers\Exporters\Product\Exporter)
     wie ein normales Attribut auswählbar sein und den berechneten Wert ausgeben.
   - Muss in der Produkt-Detailansicht (read-only) angezeigt werden können.

4. UI:
   - Formel-Editor im Attribut-Formular (Textfeld reicht für v1, Autovervollständigung
     für Attribut-Codes ist ein nice-to-have, kein Muss).
   - Validierung der Formel beim Speichern (Syntaxcheck + Zirkularitätscheck), klare
     Fehlermeldung statt stillem Fehlschlag.

5. Kompatibilität:
   - Bestehende Importe/Exporte, Varianten-Strukturen und die Completeness-Berechnung
     dürfen durch berechnete Attribute nicht brechen. Berechnete Attribute sollten
     standardmäßig NICHT in die Vollständigkeits-Pflichtfelder einfließen und nicht
     per CSV-Import beschreibbar sein (read-only/derived).
   - Mehrsprachigkeit: Formel kann pro Locale unterschiedliche Quellattribute nutzen
     oder global gelten — bitte Design-Entscheidung dokumentieren.

## Vorgehen

- Du arbeitest in diesem isolierten Git-Worktree/Branch (`feature/calculated-attributes`),
  nicht im laufenden PoC-Container. Committe deine Arbeit in sinnvollen, kleinen
  Commits mit aussagekräftigen Messages.
- Schreibe ZUERST ein kurzes Konzept/Design-Dokument nach `docs/calculated-attributes-design.md`
  (Datenmodell/Migration, Formel-Sprache inkl. unterstützter Funktionen, Caching-
  Strategie inkl. Begründung, UI-Skizze, offene Fragen). Committe es separat, bevor
  du mit der Implementierung weitermachst.
- Implementiere danach das Feature inkl. Migration(en) mit Rollback-Pfad.
- Schreibe Unit-/Feature-Tests für mindestens: einfache Konkatenation, den
  Kategorie-Pfad-Fall, Zirkularitäts-Erkennung, Export-Integration, Verhalten bei
  fehlenden/leeren Quellwerten. Führe die Tests aus und stelle sicher, dass sie
  grün sind (nutze eine isolierte Testumgebung/SQLite wie in diesem Repo üblich,
  nicht die laufende Produktions-DB).
- Liefere eine kurze README/Doku (`docs/calculated-attributes-usage.md`), wie man ein
  berechnetes Attribut anlegt, mit genau dem Artikelfamilie-Beispiel als Walkthrough.
- Halte dich an den Code-Stil des Repos (Pint/vorhandene Patterns), räume temporäre
  Dateien auf.
- Fasse am Ende zusammen: was gebaut wurde, welche Tests mit welchem Ergebnis liefen,
  welche Dateien geändert/neu sind, und ob/wie das als generisches Upstream-PR an
  unopim/unopim eingereicht werden könnte (keine kundenspezifische Logik einbauen,
  nur das generische Feature).
- Wenn du an einer Stelle eine Grundsatzentscheidung triffst (z.B. Caching-Strategie,
  Formel-Sprache), dokumentiere kurz die Alternativen und warum du dich so entschieden
  hast — es gibt keine Rückfragemöglichkeit während des Laufs, triff selbstständig die
  fachlich sinnvollste Entscheidung.
