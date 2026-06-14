<?php
/* =========================================================================
   render-libro.php — Arma libro.html completo (hasta 365 recetas)
   - Conserva el encabezado/CSS, la portada y las recetas existentes (001-036)
   - Lee recipes-b01.json..b20.json (recetas 037+) y genera sus paginas con
     ESPACIO RESERVADO de imagen (placeholder), listas para foto futura
   - Reconstruye el indice agrupado por categorias y paginado a tamano A4
   Ejecutar:  php render-libro.php
   ========================================================================= */

$ROOT = 'C:/Claude/Projects/ComidaSaludable';
$LIBRO = "$ROOT/libro.html";

function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$html = file_get_contents($LIBRO);
if ($html === false) { fwrite(STDERR, "No se pudo leer libro.html\n"); exit(1); }

/* ---- 1. Cortar el archivo en partes ---- */
$mkIndex = '<!-- ÍNDICE INTERACTIVO -->';
$posIndex = strpos($html, $mkIndex);
$mkR1 = '<!-- RECETA 001 -->';
$posR1 = strpos($html, $mkR1);
$posBody = strrpos($html, '</body>');
if ($posIndex === false || $posR1 === false || $posBody === false) {
  fwrite(STDERR, "Marcadores no encontrados en libro.html\n"); exit(1);
}
$headCover = substr($html, 0, $posIndex);                 // <head>+CSS + portada
// Idempotente: conserva SOLO las recetas base (001-036). Si ya se corrio antes,
// corta en la primera receta generada (037) para no duplicar.
$posR37 = strpos($html, '<!-- RECETA 037 -->');
$endExisting = ($posR37 !== false) ? $posR37 : $posBody;
$existingPages = rtrim(substr($html, $posR1, $endExisting - $posR1)); // recetas 001-036

/* ---- 2. Inyectar CSS del indice compacto (una sola vez) ---- */
if (strpos($headCover, '.cidx-line') === false) {
  $css = "\n  /* Indice compacto (libro completo) */\n"
    . "  .cidx-cat{font-family:Georgia,'Times New Roman',serif;font-size:12pt;color:#22341f;font-weight:700;margin:3mm 0 1.5mm;border-bottom:2px solid #d9622b;padding-bottom:1mm;}\n"
    . "  .cidx-cat:first-of-type{margin-top:0;}\n"
    . "  .cidx-line{display:flex;align-items:baseline;gap:8px;text-decoration:none;color:inherit;font-size:9.5pt;padding:1.1mm 0;border-bottom:1px dotted #e2e8dc;}\n"
    . "  .cidx-line .n{color:#3a7d34;font-weight:700;width:13mm;flex-shrink:0;}\n"
    . "  .cidx-line .t{flex:1;}\n"
    . "  .cidx-line .k{color:#d9622b;font-weight:700;flex-shrink:0;white-space:nowrap;}\n";
  $headCover = str_replace('</style>', $css . '</style>', $headCover);
}

/* ---- 3. Metadatos de las 36 recetas existentes ---- */
$existing = [
  [1,'Desayuno','Avena Overnight con Frutos Rojos y Chía',320],
  [2,'Almuerzo','Bowl de Pollo a la Plancha con Quinoa y Aguacate',480],
  [3,'Cena','Salmón al Horno con Espárragos y Limón',410],
  [4,'Snack','Hummus Casero con Crudités',190],
  [5,'Bebida','Smoothie Verde Proteico',250],
  [6,'Desayuno','Tostada de Aguacate con Huevo Poché',340],
  [7,'Desayuno','Panqueques de Avena y Plátano',380],
  [8,'Almuerzo','Ensalada Mediterránea de Garbanzos',420],
  [9,'Cena','Sopa de Lentejas con Verduras',310],
  [10,'Snack','Yogur Griego con Granola y Frutos Rojos',240],
  [11,'Snack','Energy Balls de Dátil y Cacao',180],
  [12,'Postre','Mousse de Chocolate y Aguacate',210],
  [13,'Desayuno','Huevos Revueltos con Espinaca y Champiñones',290],
  [14,'Desayuno','Smoothie Bowl de Plátano y Maní',340],
  [15,'Almuerzo','Bowl de Quinoa con Vegetales Asados y Garbanzos',420],
  [16,'Almuerzo','Wrap Integral de Pollo y Hummus',380],
  [17,'Cena','Pechuga de Pollo al Limón con Brócoli',350],
  [18,'Cena','Tacos de Pescado con Repollo Morado',330],
  [19,'Cena','Salteado de Tofu y Verduras con Arroz Integral',400],
  [20,'Snack','Edamame al Vapor con Sal de Mar',150],
  [21,'Snack','Manzana con Mantequilla de Almendra',210],
  [22,'Bebida','Batido Verde de Piña y Espinaca',160],
  [23,'Postre','Fresas con Yogur Griego y Miel',180],
  [24,'Postre','Galletas de Avena y Plátano',150],
  [25,'Desayuno','Tortilla de Claras con Vegetales',200],
  [26,'Desayuno','Pudín de Chía con Mango',280],
  [27,'Desayuno','Tostada Francesa Integral',330],
  [28,'Almuerzo','Ensalada César con Pollo (ligera)',390],
  [29,'Almuerzo','Arroz Integral con Vegetales y Huevo',430],
  [30,'Almuerzo','Sopa de Pollo con Fideos Integrales',340],
  [31,'Cena','Pescado al Vapor con Vegetales',300],
  [32,'Cena','Berenjenas Rellenas de Pavo',360],
  [33,'Cena','Pizza de Coliflor con Vegetales',340],
  [34,'Snack','Mix de Frutos Secos y Semillas',200],
  [35,'Bebida','Agua de Jamaica sin Azúcar',30],
  [36,'Postre','Brownies de Frijol Negro',160],
];

/* ---- 4. Leer los lotes JSON (recetas nuevas) en orden ---- */
$new = [];
$num = 37;
$leidos = 0; $saltados = [];
for ($i = 1; $i <= 20; $i++) {
  $bf = sprintf("%s/recipes-b%02d.json", $ROOT, $i);
  if (!is_file($bf)) { $saltados[] = "b$i (falta)"; continue; }
  $j = json_decode(file_get_contents($bf), true);
  if (!is_array($j) || empty($j['recipes'])) { $saltados[] = "b$i (json invalido)"; continue; }
  $cat = isset($j['category']) ? $j['category'] : 'Snack';
  foreach ($j['recipes'] as $r) {
    if (empty($r['title'])) continue;
    $r['num'] = $num;
    $r['cat'] = $cat;
    $new[] = $r;
    $num++;
    $leidos++;
  }
}

/* ---- 5. Plantilla de pagina de receta (con placeholder de imagen) ---- */
function recipePage($r) {
  $n = sprintf('%03d', $r['num']);
  $cat = h($r['cat']);
  $title = h($r['title']);
  $cal = (int)($r['calories'] ?? 0);
  $port = h($r['portions'] ?? '1 porción');
  $time = h($r['time'] ?? '');
  $diff = h($r['difficulty'] ?? 'Fácil');
  $p = (int)($r['protein_g'] ?? 0); $c = (int)($r['carbs_g'] ?? 0);
  $f = (int)($r['fat_g'] ?? 0); $fi = (int)($r['fiber_g'] ?? 0);
  $ing = '';
  foreach (($r['ingredients'] ?? []) as $x) { $ing .= "        <li>" . h($x) . "</li>\n"; }
  $steps = '';
  foreach (($r['steps'] ?? []) as $x) { $steps .= "        <li>" . h($x) . "</li>\n"; }
  $tips = '';
  foreach (($r['tips'] ?? []) as $x) { $tips .= "      <li>" . h($x) . "</li>\n"; }
  return <<<HTML
<!-- RECETA $n -->
<div class="page recipe" id="receta-$n">
  <div class="r-top"><span class="r-num">Receta $n</span><span class="r-right"><a class="back" href="#indice">‹ Índice</a><span class="r-cat">$cat</span></span></div>
  <div class="r-title">$title</div>
  <div class="r-img ph">📷 Foto próximamente</div>
  <div class="r-meta"><span>🍽 $port</span><span>⏱ $time</span><span>📊 $diff</span><span class="cal">$cal kcal</span></div>
  <div class="r-cols">
    <div class="r-col">
      <div class="r-h">Ingredientes</div>
      <ul>
$ing      </ul>
      <div class="nutri"><b>Por porción:</b><br>Calorías: <b>$cal kcal</b> · Proteína: $p g<br>Carbohidratos: $c g · Grasas: $f g · Fibra: $fi g</div>
    </div>
    <div class="r-col">
      <div class="r-h">Preparación</div>
      <ol>
$steps      </ol>
    </div>
  </div>
  <div class="weblink">🌐 <b>www.365recetas.com</b></div>
  <div class="consejos">
    <div class="c-h">Consejos útiles</div>
    <ul>
$tips    </ul>
  </div>
  <div class="advert"><b>Advertencia:</b> Antes de hacer cambios importantes en tu dieta, sobre todo si tienes alguna condición de salud, consulta con un profesional.</div>
  <div class="footer"><b>www.365recetas.com</b> · Una receta saludable para cada día</div>
</div>
HTML;
}

$newPagesHtml = '';
foreach ($new as $r) { $newPagesHtml .= recipePage($r) . "\n\n"; }
$newPagesHtml = rtrim($newPagesHtml);

/* ---- 6. Construir el indice agrupado por categoria y paginado ---- */
$meta = [];
foreach ($existing as $e) { $meta[] = ['num'=>$e[0],'cat'=>$e[1],'title'=>$e[2],'cal'=>$e[3]]; }
foreach ($new as $r) { $meta[] = ['num'=>$r['num'],'cat'=>$r['cat'],'title'=>$r['title'],'cal'=>(int)($r['calories']??0)]; }

$catOrder = ['Desayuno'=>'🍳 Desayunos','Almuerzo'=>'🥗 Almuerzos','Cena'=>'🍽 Cenas','Snack'=>'🥑 Snacks','Bebida'=>'🥤 Bebidas','Postre'=>'🍓 Postres'];
$byCat = [];
foreach (array_keys($catOrder) as $k) { $byCat[$k] = []; }
foreach ($meta as $m) { if (!isset($byCat[$m['cat']])) $byCat[$m['cat']]=[]; $byCat[$m['cat']][] = $m; }
foreach ($byCat as $k=>&$arr) { usort($arr, function($a,$b){ return $a['num']<=>$b['num']; }); } unset($arr);

$items = [];
foreach ($catOrder as $k=>$label) {
  if (empty($byCat[$k])) continue;
  $items[] = ['type'=>'cat','label'=>$label];
  foreach ($byCat[$k] as $m) { $items[] = ['type'=>'row'] + $m; }
}

$CAP = 40;
$pages = []; $cur = []; $units = 0; $first = true;
foreach ($items as $it) {
  $cost = $it['type']==='cat' ? 3 : 1;
  $tope = $first ? ($CAP - 4) : $CAP;
  if ($it['type']==='cat' && ($units + 6) > $tope && !empty($cur)) {
    $pages[] = $cur; $cur = []; $units = 0; $first = false; $tope = $CAP;
  }
  if ($units + $cost > $tope && !empty($cur)) {
    $pages[] = $cur; $cur = []; $units = 0; $first = false;
  }
  $cur[] = $it; $units += $cost;
}
if (!empty($cur)) $pages[] = $cur;

$indexHtml = "<!-- ÍNDICE INTERACTIVO -->\n";
foreach ($pages as $pi => $pg) {
  $id = $pi === 0 ? 'indice' : 'indice-' . ($pi + 1);
  $indexHtml .= "<div class=\"page index\" id=\"$id\">\n";
  if ($pi === 0) {
    $indexHtml .= "  <div class=\"idx-title\">Índice de recetas</div>\n";
    $indexHtml .= "  <div class=\"idx-sub\">" . count($meta) . " recetas · toca cualquiera para ir a ella ↓</div>\n";
  } else {
    $indexHtml .= "  <div class=\"idx-title\">Índice · continuación</div>\n";
  }
  foreach ($pg as $it) {
    if ($it['type'] === 'cat') {
      $indexHtml .= "  <div class=\"cidx-cat\">" . $it['label'] . "</div>\n";
    } else {
      $n = sprintf('%03d', $it['num']);
      $indexHtml .= "  <a class=\"cidx-line\" href=\"#receta-$n\"><span class=\"n\">$n</span><span class=\"t\">" . h($it['title']) . "</span><span class=\"k\">" . $it['cal'] . " kcal</span></a>\n";
    }
  }
  $indexHtml .= "  <div class=\"footer\"><b>www.365recetas.com</b> · Una receta saludable para cada día</div>\n";
  $indexHtml .= "</div>\n\n";
}

/* ---- 6b. Corrector de tildes (palabras frecuentes; el contenido nuevo vino sin acentos) ---- */
$fixmap = [
  'porcion'=>'porción','Porcion'=>'Porción',
  'facil'=>'fácil','Facil'=>'Fácil',
  'limon'=>'limón','Limon'=>'Limón',
  'platano'=>'plátano','Platano'=>'Plátano','platanos'=>'plátanos',
  'pure'=>'puré','Pure'=>'Puré',
  'azucar'=>'azúcar','Azucar'=>'Azúcar',
  'brocoli'=>'brócoli','Brocoli'=>'Brócoli',
  'pina'=>'piña','Pina'=>'Piña','pinas'=>'piñas',
  'champinon'=>'champiñón','Champinon'=>'Champiñón','champinones'=>'champiñones',
  'mani'=>'maní','Mani'=>'Maní',
  'preparacion'=>'preparación',
  'rapido'=>'rápido','rapida'=>'rápida','rapidamente'=>'rápidamente',
  'nutricion'=>'nutrición',
  'proteina'=>'proteína','proteinas'=>'proteínas',
  'almibar'=>'almíbar',
  'anade'=>'añade','anadir'=>'añadir','anades'=>'añades','anada'=>'añada',
  'coccion'=>'cocción',
  'pequeno'=>'pequeño','pequena'=>'pequeña','pequenos'=>'pequeños','pequenas'=>'pequeñas',
  'sarten'=>'sartén','Sarten'=>'Sartén',
  'salmon'=>'salmón','Salmon'=>'Salmón',
  'albondigas'=>'albóndigas','Albondigas'=>'Albóndigas',
  'esparragos'=>'espárragos','Esparragos'=>'Espárragos',
  'jamon'=>'jamón','Jamon'=>'Jamón',
  'oregano'=>'orégano','Oregano'=>'Orégano',
  'maiz'=>'maíz','Maiz'=>'Maíz',
  'datiles'=>'dátiles','Datiles'=>'Dátiles','datil'=>'dátil',
  'sesamo'=>'sésamo','Sesamo'=>'Sésamo',
  'rabano'=>'rábano','rabanos'=>'rábanos',
  'almendra'=>'almendra',
  'kcal'=>'kcal',
  'rapidos'=>'rápidos','rapidas'=>'rápidas',
  'clasico'=>'clásico','clasica'=>'clásica',
  'crujiente'=>'crujiente',
  'pure'=>'puré',
];
$fixfn = function($s) use ($fixmap){ foreach($fixmap as $k=>$v){ $s = preg_replace('/\b'.preg_quote($k,'/').'\b/u', $v, $s); } return $s; };
$indexHtml    = $fixfn($indexHtml);
$newPagesHtml = $fixfn($newPagesHtml);

/* ---- 7. Escribir libro.html ---- */
$out = $headCover . $indexHtml . $existingPages . "\n\n" . $newPagesHtml . "\n\n</body>\n</html>\n";
file_put_contents($LIBRO, $out);

$total = count($meta);
echo "Recetas nuevas leidas: $leidos\n";
if ($saltados) echo "Lotes saltados: " . implode(', ', $saltados) . "\n";
echo "Total recetas en el libro: $total\n";
echo "Paginas de indice: " . count($pages) . "\n";
echo "libro.html reescrito.\n";
