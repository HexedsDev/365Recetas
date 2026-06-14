<?php
/* =========================================================================
   gen-fotos.php — Lista las recetas que AUN no tienen foto y arma su prompt.
   Salida: fotos-pendientes.jsonl  (un objeto JSON por linea: {num,file,prompt})
   Ejecutar:  php gen-fotos.php
   ========================================================================= */
$ROOT = __DIR__;

// Receta base 036 (no esta en los JSON; tiene placeholder en libro.html)
$lista = [ ['num'=>36, 'cat'=>'Postre', 'title'=>'Brownies de Frijol Negro'] ];

// Recetas 037+ desde los lotes JSON, en orden
$num = 37;
for ($i = 1; $i <= 20; $i++) {
  $bf = sprintf("%s/recipes-b%02d.json", $ROOT, $i);
  if (!is_file($bf)) continue;
  $j = json_decode(file_get_contents($bf), true);
  if (!is_array($j) || empty($j['recipes'])) continue;
  $cat = $j['category'] ?? 'Snack';
  foreach ($j['recipes'] as $r) {
    if (empty($r['title'])) continue;
    $lista[] = ['num'=>$num, 'cat'=>$cat, 'title'=>$r['title']];
    $num++;
  }
}

$meal = ['Desayuno'=>'breakfast','Almuerzo'=>'lunch','Cena'=>'dinner','Snack'=>'snack','Bebida'=>'drink','Postre'=>'dessert'];

$out = '';
$pend = 0; $ya = 0;
foreach ($lista as $r) {
  $n = sprintf('%03d', $r['num']);
  $file = "receta-$n.png";
  if (file_exists("$ROOT/imagenes/$file")) { $ya++; continue; }   // ya tiene foto
  $m = $meal[$r['cat']] ?? 'dish';
  $prompt = "Professional food photography of " . $r['title']
    . ", a healthy " . $m . " dish, beautifully plated, natural soft light, 45-degree or top-down angle, "
    . "clean bright background, fresh and appetizing, high detail, realistic, no text, no words, no letters";
  $out .= json_encode(['num'=>$n, 'file'=>$file, 'prompt'=>$prompt], JSON_UNESCAPED_UNICODE) . "\n";
  $pend++;
}
file_put_contents("$ROOT/fotos-pendientes.jsonl", $out);
echo "Con foto ya: $ya | Pendientes: $pend\n";
echo "Manifiesto: fotos-pendientes.jsonl\n";
