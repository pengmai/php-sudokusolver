<?php
require_once 'csp_base.php';

$input = [
  [0, 0, 0, 0, 0, 0, 0, 0, 0],
  [0, 0, 0, 0, 0, 0, 0, 0, 0],
  [0, 0, 0, 0, 0, 0, 0, 0, 0],
  [0, 0, 0, 0, 0, 0, 0, 0, 0],
  [0, 0, 0, 0, 0, 0, 0, 0, 0],
  [0, 0, 0, 0, 0, 0, 0, 0, 0],
  [0, 0, 0, 0, 0, 0, 0, 0, 0],
  [0, 0, 0, 0, 0, 0, 0, 0, 0],
  [0, 0, 0, 0, 0, 0, 0, 0, 0]
];

// Populate the board of variables.
$scope = [];
$board = [];
for ($i = 0; $i < 9; $i++) {
  $row = [];
  for ($j = 0; $j < 9; $j++) {
    $val = $input[$i][$j];
    if ($val === 0) {
      $row[] = new Variable('Square' . $j . $i, [1, 2, 3, 4, 5, 6, 7, 8, 9]);
    } else {
      $var = new Variable('Square' . $j . $i, [$val]);
      $var->assign($val);
      $row[] = $var;
    }
  }
  $board[] = $row;
  $scope = array_merge($scope, $row);
}

$csp = new CSP('Sudoku', $scope);

for ($i = 0; $i < 9; $i++) {
  // Add the row constraints
  $row = $board[$i];
  $con = new Constraint('Row' . $i, $row);
  $csp->add_constraint($con);

  // Add the column constraints.
  $col = array_column($board, $i);
  $con = new Constraint('Col' . $i, $col);
  $csp->add_constraint($con);
}

for ($i = 0; $i < 9; $i += 3) {
  for ($j = 0; $j < 9; $j += 3) {
    $sub_square = [];
    for ($di = 0; $di < 3; $di++) {
      for ($dj = 0; $dj < 3; $dj++) {
        $sub_square[] = $board[$i + $di][$j + $dj];
      }
    }
    $con = new Constraint('SubSquare' . $i . $j, $sub_square);
    $csp->add_constraint($con);
  }
}

$btracker = new BT($csp);

$btracker->bt_search();
print 'Memory in bytes used: ' . memory_get_usage(true) . "\n";
?>
