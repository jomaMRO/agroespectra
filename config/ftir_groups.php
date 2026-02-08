<?php

/**
 * FTIR functional group rules (wavenumber cm-1)
 *
 * priority:
 *   - Menor = aparece más arriba (más “importante”/característico).
 * score en su lógica:
 *   - Mide cercanía al centro del rango (no “certeza química”).
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Región X–H (3700–2500)
    |--------------------------------------------------------------------------
    */

    // O–H (valencia) – ancho (H-bond). Su tabla: 3100–3200
    ['group_name'=>'O–H (enlace H, banda ancha)', 'bond'=>'O–H', 'wn_min'=>3100, 'wn_max'=>3550, 'priority'=>10],

    // O–H libre (sin enlace H) ~3600 (se deja una ventana pequeña)
    ['group_name'=>'O–H (libre, sin enlace H)', 'bond'=>'O–H', 'wn_min'=>3580, 'wn_max'=>3650, 'priority'=>12],

    // N–H 3500–3300 (su tabla)
    ['group_name'=>'N–H (valencia)', 'bond'=>'N–H', 'wn_min'=>3300, 'wn_max'=>3500, 'priority'=>14],

    // C–H sp2 / aromático (valencia) ~3100–3000
    ['group_name'=>'=C–H (sp2/aromático) estiramiento', 'bond'=>'=C–H', 'wn_min'=>3000, 'wn_max'=>3100, 'priority'=>16],

    // C–H sp3 (valencia) 2960–2850 (imagen 1: 2937, 2917, 2869)
    ['group_name'=>'C–H (sp3 alifático) estiramiento', 'bond'=>'Csp3–H', 'wn_min'=>2850, 'wn_max'=>2965, 'priority'=>16],


    /*
    |--------------------------------------------------------------------------
    | Región triples / cumulenos (2300–2000)
    |--------------------------------------------------------------------------
    */

    // C≡N ~2250 (su tabla) - se acota mejor
    ['group_name'=>'Nitrilo', 'bond'=>'C≡N', 'wn_min'=>2230, 'wn_max'=>2270, 'priority'=>8],

    // Isocianato ~2270 (su tabla)
    ['group_name'=>'Isocianato', 'bond'=>'N=C=O', 'wn_min'=>2250, 'wn_max'=>2290, 'priority'=>9],

    // Alquino C≡C 2100–2300 (su tabla)
    ['group_name'=>'Alquino', 'bond'=>'C≡C', 'wn_min'=>2100, 'wn_max'=>2300, 'priority'=>20],

    // Isotiocianato ~2150 (su tabla)
    ['group_name'=>'Isotiocianato', 'bond'=>'N=C=S', 'wn_min'=>2130, 'wn_max'=>2170, 'priority'=>12],


    /*
    |--------------------------------------------------------------------------
    | Carbonilos / dobles enlaces (1850–1500)
    |--------------------------------------------------------------------------
    */

    // Anhídridos 1850–1740 (su tabla)
    ['group_name'=>'Anhídrido (C=O)', 'bond'=>'C=O', 'wn_min'=>1740, 'wn_max'=>1850, 'priority'=>9],

    // Cloruro de acilo -COCl 1815–1785 (su tabla)
    ['group_name'=>'Cloruro de acilo (C=O)', 'bond'=>'C=O', 'wn_min'=>1785, 'wn_max'=>1815, 'priority'=>10],

    // Lactonas
    ['group_name'=>'Lactona (C=O) δ', 'bond'=>'C=O', 'wn_min'=>1735, 'wn_max'=>1750, 'priority'=>12],
    ['group_name'=>'Lactona (C=O) γ', 'bond'=>'C=O', 'wn_min'=>1760, 'wn_max'=>1780, 'priority'=>12],

    // Ésteres (C=O) 1750–1735 (su tabla)
    ['group_name'=>'Éster (C=O)', 'bond'=>'C=O', 'wn_min'=>1735, 'wn_max'=>1750, 'priority'=>11],

    // Éster α,β-insaturado 1750–1715 (su tabla)
    ['group_name'=>'Éster α,β-insaturado (C=O)', 'bond'=>'C=O', 'wn_min'=>1715, 'wn_max'=>1750, 'priority'=>13],

    // Ácido carboxílico (C=O) 1725–1700 (su tabla)
    ['group_name'=>'Ácido carboxílico (C=O)', 'bond'=>'C=O', 'wn_min'=>1700, 'wn_max'=>1725, 'priority'=>12],

    // Cetonas 1725–1700 (su tabla)
    ['group_name'=>'Cetona (C=O)', 'bond'=>'C=O', 'wn_min'=>1700, 'wn_max'=>1725, 'priority'=>12],

    // Aldehídos 1740–1720 (su tabla)
    ['group_name'=>'Aldehído (C=O)', 'bond'=>'C=O', 'wn_min'=>1720, 'wn_max'=>1740, 'priority'=>12],

    // Carbonilo conjugado (α,β-insaturado) 1715–1660 (su tabla)
    ['group_name'=>'Carbonilo conjugado (α,β-insaturado)', 'bond'=>'C=O', 'wn_min'=>1660, 'wn_max'=>1715, 'priority'=>13],

    // Amidas 1690–1630 (su tabla)
    ['group_name'=>'Amida (C=O)', 'bond'=>'C=O / N–H', 'wn_min'=>1630, 'wn_max'=>1690, 'priority'=>12],

    // Carboxilato COO- (imagen 1: puede coincidir ~1610–1625 y ~1400)
    ['group_name'=>'Carboxilato COO⁻ (asim.)', 'bond'=>'COO⁻', 'wn_min'=>1540, 'wn_max'=>1625, 'priority'=>14],
    ['group_name'=>'Carboxilato COO⁻ (sim.)', 'bond'=>'COO⁻', 'wn_min'=>1360, 'wn_max'=>1450, 'priority'=>14],

    // Aromáticos C=C (imagen 1 menciona aromático alrededor 1403; típico 1600–1450)
    ['group_name'=>'Anillo aromático (C=C)', 'bond'=>'C=C (aromático)', 'wn_min'=>1450, 'wn_max'=>1605, 'priority'=>18],

    // C=N- 1690–1480 (su tabla; es muy amplio, por eso prioridad más baja)
    ['group_name'=>'Imina / C=N', 'bond'=>'C=N', 'wn_min'=>1480, 'wn_max'=>1690, 'priority'=>25],

    // NO2 1650–1500 y 1400–1250 (su tabla)
    ['group_name'=>'Nitro (NO₂) banda alta', 'bond'=>'N–O', 'wn_min'=>1500, 'wn_max'=>1650, 'priority'=>22],
    ['group_name'=>'Nitro (NO₂) banda baja', 'bond'=>'N–O', 'wn_min'=>1250, 'wn_max'=>1400, 'priority'=>22],

    // C=C=C ~1950 (su tabla)
    ['group_name'=>'Aleno', 'bond'=>'C=C=C', 'wn_min'=>1930, 'wn_max'=>1970, 'priority'=>30],


    /*
    |--------------------------------------------------------------------------
    | Región huella digital (1400–400)
    |--------------------------------------------------------------------------
    */

    // C–O (imagen 1: ~1020) y rango típico 1260–1000
    ['group_name'=>'C–O (alcohol/éter/éster)', 'bond'=>'C–O', 'wn_min'=>1000, 'wn_max'=>1260, 'priority'=>18],

    // S=O 1070–1010 (su tabla)
    ['group_name'=>'S=O (sulfóxido/sulfato)', 'bond'=>'S=O', 'wn_min'=>1010, 'wn_max'=>1070, 'priority'=>16],

    // Sulfonas 1350–1300 y 1150–1100 (su tabla)
    ['group_name'=>'Sulfona (S=O)', 'bond'=>'S=O', 'wn_min'=>1300, 'wn_max'=>1350, 'priority'=>20],
    ['group_name'=>'Sulfona (S=O)', 'bond'=>'S=O', 'wn_min'=>1100, 'wn_max'=>1150, 'priority'=>20],

    // Sulfonamidas y sulfonatos 1370–1300 y 1180–1140 (su tabla)
    ['group_name'=>'Sulfonamida/Sulfonato (S=O)', 'bond'=>'S=O', 'wn_min'=>1300, 'wn_max'=>1370, 'priority'=>20],
    ['group_name'=>'Sulfonamida/Sulfonato (S=O)', 'bond'=>'S=O', 'wn_min'=>1140, 'wn_max'=>1180, 'priority'=>20],

    // C–H aromático fuera del plano (imagen 1: 770 cm-1; típico 900–650)
    ['group_name'=>'C–H aromático (fuera del plano)', 'bond'=>'Ar–H oop', 'wn_min'=>650, 'wn_max'=>900, 'priority'=>18],

    // Halogenuros (su tabla)
    ['group_name'=>'C–Cl', 'bond'=>'C–Cl', 'wn_min'=>580, 'wn_max'=>780, 'priority'=>26],
    ['group_name'=>'C–Br', 'bond'=>'C–Br', 'wn_min'=>560, 'wn_max'=>800, 'priority'=>26],
    ['group_name'=>'C–I',  'bond'=>'C–I',  'wn_min'=>500, 'wn_max'=>600, 'priority'=>28],

    // C–F (su tabla, pero es demasiado amplio; prioridad alta para que no “domine”)
    ['group_name'=>'C–F (rango amplio; interpretar con cautela)', 'bond'=>'C–F', 'wn_min'=>1000, 'wn_max'=>1400, 'priority'=>90],
];
