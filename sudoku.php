<?php
require 'csp_base.php';
/**
 * A Sudoku solving algorithm that represents the game as a Constraint
 * Satisfaction Problem (CSP) and uses Generalized Arc Consistency to solve the
 * given board.
 *
 * Written by Jacob Mai Peng.
 */
$input = [
  [0, 0, 0, 0, 1, 0, 8, 0, 0],
  [8, 0, 0, 6, 0, 0, 0, 5, 0],
  [4, 5, 0, 9, 0, 3, 0, 7, 0],
  [0, 0, 0, 3, 0, 0, 0, 9, 0],
  [9, 0, 7, 0, 0, 0, 4, 0, 3],
  [0, 3, 0, 0, 0, 1, 0, 0, 0],
  [0, 1, 0, 8, 0, 4, 0, 6, 5],
  [0, 4, 0, 0, 0, 6, 0, 0, 1],
  [0, 0, 6, 0, 7, 0, 0, 0, 0]
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
?>
