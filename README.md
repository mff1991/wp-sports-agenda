# CHB Resultats — Agenda i partits per a WordPress

Plugin modular per a WordPress dissenyat per a la gestió centralitzada, automatització i publicació de calendaris esportius, agendes de partits i marcadors en temps real.

---

## Descripció

El mòdul unifica el control de l'activitat esportiva des de l'escriptori de WordPress, evitant dependències de tercers. Permet planificar les jornades, destacar esdeveniments clau i actualitzar els marcadors dels partits un cop finalitzats. Totes les vistes generades al frontend (ja sigui a través de shortcodes o mitjançant el giny d'Elementor) són totalment síncrones i s'adapten completament a pantalles mòbils o tauletes.

---

## Característiques tècniques

* **Custom Post Type (CPT):** Panell d'administració独立 per a la gestió aïllada dels partits.
* **Taxonomies personalitzades:** Classificació indexada de la base de dades per seccions i categories.
* **Filtratge asíncron (JS):** Motor de cerca en frontend que avalua els atributs de la graella a l'instant sense recàrrega de pàgina.
* **Integració modular:** Arquitectura adaptada per renderitzar mitjançant shortcodes natius o com a widget personalitzat.
* **Estructura responsive:** fulls d'estils CSS optimitzats per assegurar la visualització de taules de classificació en qualsevol dispositiu.

---

## Estructura de fitxers

```text
chb-resultats/
├── chb-resultats.php          # Inicialitzador i metadades del plugin
├── includes/                  # Lògica de control i backend
│   ├── chb-resultats.php      # Controladors principals del cicle de vida
│   ├── cpt.php                # Registre i configuració del Custom Post Type
│   ├── taxonomies.php         # Definició del sistema de categories i taxonomies
│   ├── meta-boxes.php         # Camps personalitzats per al formulari de dades
│   └── class-widget.php       # Desenvolupament de la interfície del mòdul per a Elementor
└── assets/                    # Recursos públics del frontend
    ├── css/
    │   └── style.css          # Estils, maquetació de les graelles i disseny responsive
    └── js/
        └── filter.js          # Lògica JavaScript per a l'execució de filtres dinàmics
