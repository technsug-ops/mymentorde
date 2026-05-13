<?php

/**
 * Almanya coğrafi referans verisi — program-search filtreleri için.
 *
 * 16 Bundesländer (eyalet) + city→state mapping + büyük şehir listesi.
 * Aynı şehrin EN/DE varyantları ayrı entry — Munich/München, Cologne/Köln
 * (CITY_ALIAS_PAIRS controller'da merge eder, ama mapping her iki yazımı
 * da kapsamalı ki filter doğru eşleştirsin).
 */

return [

    // ── EN ↔ DE şehir adı eşdeğerleri (kaynak verisinde duplikasyon) ────
    // Canonical isim = pair'in SON elemanı (daha yaygın yazım).
    'city_alias_pairs' => [
        ['Munich', 'München'],
        ['Cologne', 'Köln'],
        ['Hanover', 'Hannover'],
        ['Brunswick', 'Braunschweig'],
        ['Nuremberg', 'Nürnberg'],
        ['Constance', 'Konstanz'],
        ['Frankfurt/Main', 'Frankfurt a.M.', 'Frankfurt am Main'],
        ['Aix-la-Chapelle', 'Aachen'],
        ['Freiburg i. Br.', 'Freiburg', 'Freiburg im Breisgau'],
    ],

    // ── 16 Bundesländer ────────────────────────────────────────────
    'states' => [
        'baden_wuerttemberg'    => 'Baden-Württemberg',
        'bayern'                => 'Bayern',
        'berlin'                => 'Berlin',
        'brandenburg'           => 'Brandenburg',
        'bremen'                => 'Bremen',
        'hamburg'               => 'Hamburg',
        'hessen'                => 'Hessen',
        'mecklenburg_vorpommern'=> 'Mecklenburg-Vorpommern',
        'niedersachsen'         => 'Niedersachsen',
        'nordrhein_westfalen'   => 'Nordrhein-Westfalen',
        'rheinland_pfalz'       => 'Rheinland-Pfalz',
        'saarland'              => 'Saarland',
        'sachsen'               => 'Sachsen',
        'sachsen_anhalt'        => 'Sachsen-Anhalt',
        'schleswig_holstein'    => 'Schleswig-Holstein',
        'thueringen'            => 'Thüringen',
    ],

    // ── Büyük şehir listesi (500K+ nüfus + ana metropol) ──────────
    // EN ve DE varyantları dahil (DB'deki ham veride ikisi de var).
    'big_cities' => [
        'Berlin',
        'Hamburg',
        'München', 'Munich',
        'Köln', 'Cologne',
        'Frankfurt am Main', 'Frankfurt/Main', 'Frankfurt a.M.',
        'Stuttgart',
        'Düsseldorf',
        'Leipzig',
        'Dortmund',
        'Essen',
        'Bremen',
        'Dresden',
        'Hannover', 'Hanover',
        'Nürnberg', 'Nuremberg',
    ],

    // ── Şehir → Eyalet mapping ────────────────────────────────────
    // Top kullanılan ~80 şehir kapsanır. Geri kalan küçük yerleşim için
    // eyalet filtresi sonuç vermez (kullanıcı küçük şehir adıyla aratır).
    'city_to_state' => [
        // Berlin (şehir-devlet)
        'Berlin' => 'berlin',

        // Hamburg (şehir-devlet)
        'Hamburg' => 'hamburg',

        // Bremen (şehir-devlet)
        'Bremen' => 'bremen',
        'Bremerhaven' => 'bremen',

        // Bayern
        'München' => 'bayern', 'Munich' => 'bayern',
        'Nürnberg' => 'bayern', 'Nuremberg' => 'bayern',
        'Augsburg' => 'bayern',
        'Würzburg' => 'bayern',
        'Regensburg' => 'bayern',
        'Erlangen' => 'bayern',
        'Bayreuth' => 'bayern',
        'Bamberg' => 'bayern',
        'Passau' => 'bayern',
        'Ingolstadt' => 'bayern',
        'Eichstätt' => 'bayern',
        'Coburg' => 'bayern',
        'Aschaffenburg' => 'bayern',
        'Amberg' => 'bayern',
        'Ansbach' => 'bayern',
        'Deggendorf' => 'bayern',
        'Landshut' => 'bayern',
        'Memmingen' => 'bayern',
        'Rosenheim' => 'bayern',
        'Weihenstephan' => 'bayern',
        'Weiden' => 'bayern',
        'Schweinfurt' => 'bayern',
        'Straubing' => 'bayern',
        'Freising' => 'bayern',
        'Hof' => 'bayern',
        'Triesdorf' => 'bayern',
        'Neu-Ulm' => 'bayern',

        // Baden-Württemberg
        'Stuttgart' => 'baden_wuerttemberg',
        'Heidelberg' => 'baden_wuerttemberg',
        'Freiburg im Breisgau' => 'baden_wuerttemberg', 'Freiburg' => 'baden_wuerttemberg', 'Freiburg i. Br.' => 'baden_wuerttemberg',
        'Karlsruhe' => 'baden_wuerttemberg',
        'Mannheim' => 'baden_wuerttemberg',
        'Konstanz' => 'baden_wuerttemberg', 'Constance' => 'baden_wuerttemberg',
        'Tübingen' => 'baden_wuerttemberg',
        'Ulm' => 'baden_wuerttemberg',
        'Hohenheim' => 'baden_wuerttemberg',
        'Esslingen' => 'baden_wuerttemberg',
        'Pforzheim' => 'baden_wuerttemberg',
        'Reutlingen' => 'baden_wuerttemberg',
        'Aalen' => 'baden_wuerttemberg',
        'Heilbronn' => 'baden_wuerttemberg',
        'Albstadt' => 'baden_wuerttemberg',
        'Offenburg' => 'baden_wuerttemberg',
        'Furtwangen' => 'baden_wuerttemberg',
        'Schwäbisch Gmünd' => 'baden_wuerttemberg',
        'Friedrichshafen' => 'baden_wuerttemberg',
        'Biberach an der Riss' => 'baden_wuerttemberg',
        'Ravensburg' => 'baden_wuerttemberg',
        'Künzelsau' => 'baden_wuerttemberg',
        'Calw' => 'baden_wuerttemberg',
        'Mosbach' => 'baden_wuerttemberg',

        // Nordrhein-Westfalen
        'Düsseldorf' => 'nordrhein_westfalen',
        'Köln' => 'nordrhein_westfalen', 'Cologne' => 'nordrhein_westfalen',
        'Dortmund' => 'nordrhein_westfalen',
        'Essen' => 'nordrhein_westfalen',
        'Bochum' => 'nordrhein_westfalen',
        'Bonn' => 'nordrhein_westfalen',
        'Aachen' => 'nordrhein_westfalen', 'Aix-la-Chapelle' => 'nordrhein_westfalen',
        'Münster' => 'nordrhein_westfalen',
        'Wuppertal' => 'nordrhein_westfalen',
        'Bielefeld' => 'nordrhein_westfalen',
        'Paderborn' => 'nordrhein_westfalen',
        'Siegen' => 'nordrhein_westfalen',
        'Duisburg' => 'nordrhein_westfalen',
        'Krefeld' => 'nordrhein_westfalen',
        'Mönchengladbach' => 'nordrhein_westfalen',
        'Hagen' => 'nordrhein_westfalen',
        'Hamm' => 'nordrhein_westfalen',
        'Recklinghausen' => 'nordrhein_westfalen',
        'Gelsenkirchen' => 'nordrhein_westfalen',
        'Hennef' => 'nordrhein_westfalen',
        'Iserlohn' => 'nordrhein_westfalen',
        'Minden' => 'nordrhein_westfalen',
        'Soest' => 'nordrhein_westfalen',
        'Lemgo' => 'nordrhein_westfalen',
        'Herford' => 'nordrhein_westfalen',
        'Höxter' => 'nordrhein_westfalen',
        'Detmold' => 'nordrhein_westfalen',

        // Hessen
        'Frankfurt am Main' => 'hessen', 'Frankfurt/Main' => 'hessen', 'Frankfurt a.M.' => 'hessen',
        'Wiesbaden' => 'hessen',
        'Darmstadt' => 'hessen',
        'Kassel' => 'hessen',
        'Marburg' => 'hessen',
        'Giessen' => 'hessen',
        'Gießen' => 'hessen',
        'Fulda' => 'hessen',

        // Niedersachsen
        'Hannover' => 'niedersachsen', 'Hanover' => 'niedersachsen',
        'Braunschweig' => 'niedersachsen', 'Brunswick' => 'niedersachsen',
        'Osnabrück' => 'niedersachsen',
        'Göttingen' => 'niedersachsen',
        'Oldenburg' => 'niedersachsen',
        'Lüneburg' => 'niedersachsen',
        'Emden' => 'niedersachsen',
        'Hildesheim' => 'niedersachsen',
        'Wolfsburg' => 'niedersachsen',
        'Wolfenbüttel' => 'niedersachsen',
        'Vechta' => 'niedersachsen',
        'Salzgitter' => 'niedersachsen',
        'Buxtehude' => 'niedersachsen',
        'Wilhelmshaven' => 'niedersachsen',
        'Clausthal-Zellerfeld' => 'niedersachsen',

        // Sachsen
        'Leipzig' => 'sachsen',
        'Dresden' => 'sachsen',
        'Chemnitz' => 'sachsen',
        'Zwickau' => 'sachsen',
        'Freiberg' => 'sachsen',
        'Mittweida' => 'sachsen',
        'Görlitz' => 'sachsen',
        'Tharandt' => 'sachsen',

        // Sachsen-Anhalt
        'Halle' => 'sachsen_anhalt',
        'Magdeburg' => 'sachsen_anhalt',
        'Dessau' => 'sachsen_anhalt',
        'Wittenberg' => 'sachsen_anhalt',
        'Köthen' => 'sachsen_anhalt',
        'Wernigerode' => 'sachsen_anhalt',
        'Merseburg' => 'sachsen_anhalt',
        'Bernburg' => 'sachsen_anhalt',
        'Stendal' => 'sachsen_anhalt',

        // Thüringen
        'Erfurt' => 'thueringen',
        'Jena' => 'thueringen',
        'Weimar' => 'thueringen',
        'Ilmenau' => 'thueringen',
        'Schmalkalden' => 'thueringen',
        'Nordhausen' => 'thueringen',

        // Mecklenburg-Vorpommern
        'Rostock' => 'mecklenburg_vorpommern',
        'Greifswald' => 'mecklenburg_vorpommern',
        'Wismar' => 'mecklenburg_vorpommern',
        'Stralsund' => 'mecklenburg_vorpommern',
        'Neubrandenburg' => 'mecklenburg_vorpommern',

        // Brandenburg
        'Potsdam' => 'brandenburg',
        'Cottbus' => 'brandenburg', 'Cottbus/Chóśebuz' => 'brandenburg',
        'Frankfurt (Oder)' => 'brandenburg',
        'Brandenburg' => 'brandenburg',
        'Wildau' => 'brandenburg',
        'Eberswalde' => 'brandenburg',

        // Schleswig-Holstein
        'Kiel' => 'schleswig_holstein',
        'Lübeck' => 'schleswig_holstein',
        'Flensburg' => 'schleswig_holstein',
        'Heide' => 'schleswig_holstein',

        // Rheinland-Pfalz
        'Mainz' => 'rheinland_pfalz',
        'Kaiserslautern' => 'rheinland_pfalz',
        'Trier' => 'rheinland_pfalz',
        'Koblenz' => 'rheinland_pfalz',
        'Worms' => 'rheinland_pfalz',
        'Speyer' => 'rheinland_pfalz',
        'Ludwigshafen' => 'rheinland_pfalz',
        'Bingen' => 'rheinland_pfalz',
        'Pirmasens' => 'rheinland_pfalz',

        // Saarland
        'Saarbrücken' => 'saarland',
        'Saarbrücken/Saarlouis' => 'saarland',
    ],

    // ── Top Almanya üniversiteleri ─────────────────────────────────
    // Kaynak: Excellence Strategy + CHE/QS/THE ortalama sıralamaları.
    // DB'deki tam university_name_cached değerleri (EN/DE varyantları dahil)
    // — Expatrio katalog bazen aynı uni'yi iki yazımla tutuyor.

    'top_10' => [
        'Technische Universität München',
        'Ludwig-Maximilians-Universität München',
        'Universität Heidelberg',
        'RWTH Aachen University',
        'Humboldt-Universität zu Berlin',
        'Freie Universität Berlin',
        'Technische Universität Berlin',
        'Rheinische Friedrich-Wilhelms-Universität Bonn', 'University of Bonn',
        'Karlsruher Institut für Technologie',
        'University of Tübingen',
    ],

    'top_20' => [
        // Top 10 (tekrar)
        'Technische Universität München',
        'Ludwig-Maximilians-Universität München',
        'Universität Heidelberg',
        'RWTH Aachen University',
        'Humboldt-Universität zu Berlin',
        'Freie Universität Berlin',
        'Technische Universität Berlin',
        'Rheinische Friedrich-Wilhelms-Universität Bonn', 'University of Bonn',
        'Karlsruher Institut für Technologie',
        'University of Tübingen',
        // +10 daha (11-20)
        'Dresden University of Technology',
        'Universität Hamburg',
        'Albert-Ludwigs-Universität Freiburg im Breisgau', 'University of Freiburg',
        'University of Münster', 'Universität Münster',
        'Universität Konstanz', 'University of Konstanz',
        'Georg-August-Universität Göttingen', 'University of Göttingen',
        'Universität Stuttgart',
        'Technische Universität Darmstadt',
        'Goethe-Universität Frankfurt am Main', 'Goethe University Frankfurt',
        'Friedrich-Alexander-Universität Erlangen-Nürnberg',
    ],

    'top_40' => [
        // Top 20 (tekrar)
        'Technische Universität München',
        'Ludwig-Maximilians-Universität München',
        'Universität Heidelberg',
        'RWTH Aachen University',
        'Humboldt-Universität zu Berlin',
        'Freie Universität Berlin',
        'Technische Universität Berlin',
        'Rheinische Friedrich-Wilhelms-Universität Bonn', 'University of Bonn',
        'Karlsruher Institut für Technologie',
        'University of Tübingen',
        'Dresden University of Technology',
        'Universität Hamburg',
        'Albert-Ludwigs-Universität Freiburg im Breisgau', 'University of Freiburg',
        'University of Münster', 'Universität Münster',
        'Universität Konstanz', 'University of Konstanz',
        'Georg-August-Universität Göttingen', 'University of Göttingen',
        'Universität Stuttgart',
        'Technische Universität Darmstadt',
        'Goethe-Universität Frankfurt am Main', 'Goethe University Frankfurt',
        'Friedrich-Alexander-Universität Erlangen-Nürnberg',
        // +20 daha (21-40)
        'Ruhr-Universität Bochum',
        'Julius-Maximilians-Universität Würzburg',
        'Johannes Gutenberg-Universität Mainz', 'Johannes Gutenberg University Mainz',
        'University of Mannheim',
        'Heinrich Heine University Düsseldorf',
        'Universität Leipzig',
        'Friedrich-Schiller-Universität Jena',
        'Philipps-Universität Marburg',
        'Martin-Luther-Universität Halle-Wittenberg',
        'Gottfried Wilhelm Leibniz Universität Hannover',
        'Bielefeld University',
        'Christian-Albrechts-Universität zu Kiel', 'Kiel University',
        'Universität Bremen',
        'Universität Potsdam',
        'Universität Augsburg',
        'Universität des Saarlandes',
        'University of Bayreuth',
        'Justus-Liebig-Universität Gießen',
        'Universität Bielefeld',
        'TU Dresden - IHI Zittau',
    ],
];
