<?php

return [
    /**
     * Field mapping for the shipped `espr_general` preset. The preset installs
     * with every field unmapped — an operator is expected to bind them — so the
     * demo binds them to the catalog's own attributes and supplies the
     * programme-level statements that are the same for every product.
     */
    'espr_general' => [
        'dpp_gtin'                 => ['attribute' => 'ean'],
        'dpp_model_identifier'     => ['attribute' => 'product_number'],
        'dpp_material_composition' => ['attribute' => 'material'],
        'dpp_recycled_content_pct' => ['attribute' => 'recycled_content_percent'],
        'dpp_care_instructions'    => ['attribute' => 'care_instructions'],
        'dpp_warranty_terms'       => ['attribute' => 'warranty_months'],
        'dpp_energy_consumption'   => ['attribute' => 'power_output'],
        'dpp_manufacturer_name'    => ['attribute' => 'brand'],
        'dpp_country_of_origin'    => ['attribute' => 'country_of_origin'],
        'dpp_certificates'         => ['attribute' => 'certifications'],

        'dpp_substances_of_concern' => ['fixed' => [
            'en_US' => 'No substances of very high concern above 0.1% w/w are present. The full SCIP declaration is filed with ECHA under the model identifier shown above.',
            'de_DE' => 'Es sind keine besonders besorgniserregenden Stoffe über 0,1 Gew.-% enthalten. Die vollständige SCIP-Meldung ist bei der ECHA unter der oben genannten Modellkennung hinterlegt.',
            'fr_FR' => 'Aucune substance extrêmement préoccupante au-delà de 0,1 % en masse. La déclaration SCIP complète est déposée auprès de l’ECHA sous l’identifiant de modèle indiqué ci-dessus.',
        ]],

        'dpp_durability_statement' => ['fixed' => [
            'en_US' => 'Tested to 10 000 cycles of the primary wear mechanism, equivalent to eight years of typical use. Failure modes observed in testing are covered by the warranty terms below.',
            'de_DE' => 'Geprüft über 10 000 Zyklen des primären Verschleißmechanismus, entsprechend acht Jahren typischer Nutzung. Die in der Prüfung beobachteten Ausfallarten sind von den unten genannten Garantiebedingungen abgedeckt.',
            'fr_FR' => 'Testé sur 10 000 cycles du principal mécanisme d’usure, soit huit ans d’usage courant. Les modes de défaillance observés en essai sont couverts par les conditions de garantie ci-dessous.',
        ]],

        'dpp_repairability_score' => ['fixed' => [
            'en_US' => '8.4 / 10 — assessed against EN 45554 for disassembly depth, fastener type, spare-part price ratio and documentation availability.',
            'de_DE' => '8,4 / 10 — bewertet nach EN 45554 hinsichtlich Demontagetiefe, Verbindungsart, Ersatzteil-Preisverhältnis und Verfügbarkeit der Dokumentation.',
            'fr_FR' => '8,4 / 10 — évalué selon EN 45554 pour la profondeur de démontage, le type de fixation, le rapport de prix des pièces et la disponibilité de la documentation.',
        ]],

        'dpp_spare_parts_availability' => ['fixed' => [
            'en_US' => 'Wear parts are stocked for ten years from the last production date and dispatched within three working days. Prices are published and capped at 25% of the product price.',
            'de_DE' => 'Verschleißteile werden zehn Jahre ab dem letzten Produktionsdatum bevorratet und innerhalb von drei Werktagen versandt. Die Preise sind veröffentlicht und auf 25 % des Produktpreises begrenzt.',
            'fr_FR' => 'Les pièces d’usure sont stockées pendant dix ans après la dernière date de production et expédiées sous trois jours ouvrés. Les prix sont publiés et plafonnés à 25 % du prix du produit.',
        ]],

        'dpp_disassembly_guide' => ['fixed' => [
            'en_US' => 'Step-by-step disassembly is documented in the service manual, which lists every fastener, the tool required and the safe order of removal. No adhesive is used in any serviceable joint.',
            'de_DE' => 'Die schrittweise Demontage ist im Servicehandbuch dokumentiert; es listet jedes Verbindungselement, das benötigte Werkzeug und die sichere Reihenfolge auf. In wartbaren Verbindungen wird kein Klebstoff eingesetzt.',
            'fr_FR' => 'Le démontage pas à pas est décrit dans le manuel de service, qui indique chaque fixation, l’outil requis et l’ordre de dépose sûr. Aucune colle n’est utilisée dans les assemblages réparables.',
        ]],

        'dpp_end_of_life_instructions' => ['fixed' => [
            'en_US' => 'Return the product through the take-back scheme below or hand it to a WEEE collection point. Separate the battery before disposal where one is fitted; do not place it in household waste.',
            'de_DE' => 'Geben Sie das Produkt über das unten genannte Rücknahmesystem zurück oder an einer WEEE-Sammelstelle ab. Entnehmen Sie vorhandene Akkus vor der Entsorgung; nicht in den Hausmüll geben.',
            'fr_FR' => 'Retournez le produit via le programme de reprise ci-dessous ou déposez-le dans un point de collecte DEEE. Retirez la batterie éventuelle avant l’élimination ; ne pas jeter avec les ordures ménagères.',
        ]],

        'dpp_take_back_scheme' => ['fixed' => [
            'en_US' => 'Free take-back for every product we have sold, at any point in its life. Request a prepaid label from the support portal; returned units are repaired and resold, or dismantled for parts.',
            'de_DE' => 'Kostenlose Rücknahme für jedes von uns verkaufte Produkt, zu jedem Zeitpunkt seiner Lebensdauer. Fordern Sie im Support-Portal ein Freiumschlag-Etikett an; zurückgesandte Geräte werden repariert und weiterverkauft oder zerlegt.',
            'fr_FR' => 'Reprise gratuite de tout produit que nous avons vendu, à n’importe quel moment de sa vie. Demandez une étiquette prépayée sur le portail d’assistance ; les retours sont réparés et revendus, ou démontés pour pièces.',
        ]],

        'dpp_carbon_footprint' => ['fixed' => [
            'en_US' => 'Cradle-to-gate product carbon footprint calculated to ISO 14067, verified by an accredited third party. The figure for this model is published in the sustainability report linked from the manufacturer page.',
            'de_DE' => 'Produkt-CO₂-Fußabdruck von der Wiege bis zum Werkstor nach ISO 14067 berechnet und von einer akkreditierten Stelle verifiziert. Der Wert für dieses Modell steht im Nachhaltigkeitsbericht, der auf der Herstellerseite verlinkt ist.',
            'fr_FR' => 'Empreinte carbone du berceau à la sortie d’usine calculée selon ISO 14067 et vérifiée par un tiers accrédité. La valeur de ce modèle figure dans le rapport de durabilité lié à la page fabricant.',
        ]],

        'dpp_manufacturing_site' => ['fixed' => [
            'en_US' => 'Final assembly site and its audit status are disclosed to verified operators on request, together with the tier-1 supplier list for this model.',
            'de_DE' => 'Der Endmontagestandort und sein Auditstatus werden verifizierten Betreibern auf Anfrage offengelegt, zusammen mit der Tier-1-Lieferantenliste für dieses Modell.',
            'fr_FR' => 'Le site d’assemblage final et son statut d’audit sont communiqués aux opérateurs vérifiés sur demande, avec la liste des fournisseurs de rang 1 pour ce modèle.',
        ]],

        'dpp_supply_chain_notes' => ['fixed' => [
            'en_US' => 'Tier-1 and tier-2 suppliers are audited annually against the supplier code of conduct. Findings and corrective-action status are available to operators under NDA.',
            'de_DE' => 'Tier-1- und Tier-2-Lieferanten werden jährlich gegen den Lieferantenkodex auditiert. Feststellungen und der Status der Korrekturmaßnahmen stehen Betreibern unter NDA zur Verfügung.',
            'fr_FR' => 'Les fournisseurs de rang 1 et 2 sont audités chaque année selon le code de conduite fournisseurs. Constats et état des actions correctives sont accessibles aux opérateurs sous NDA.',
        ]],

        'dpp_declaration_of_conformity' => ['fixed' => [
            'en_US' => 'EU Declaration of Conformity issued by the manufacturer, covering the applicable directives for this product category. The signed document is released to market-surveillance authorities on request.',
            'de_DE' => 'EU-Konformitätserklärung des Herstellers für die auf diese Produktkategorie anwendbaren Richtlinien. Das unterzeichnete Dokument wird Marktüberwachungsbehörden auf Anfrage übermittelt.',
            'fr_FR' => 'Déclaration UE de conformité émise par le fabricant, couvrant les directives applicables à cette catégorie de produit. Le document signé est remis aux autorités de surveillance du marché sur demande.',
        ]],

        'dpp_test_reports' => ['fixed' => [
            'en_US' => 'Safety and performance test reports from the notified body are held on file and released to authorities on request, referenced by the model identifier in this passport.',
            'de_DE' => 'Sicherheits- und Leistungsprüfberichte der benannten Stelle werden archiviert und Behörden auf Anfrage übermittelt; sie sind über die Modellkennung in diesem Pass referenziert.',
            'fr_FR' => 'Les rapports d’essais de sécurité et de performance de l’organisme notifié sont archivés et transmis aux autorités sur demande, référencés par l’identifiant de modèle de ce passeport.',
        ]],
    ],
];
