<?php
require_once 'api.class.php';
require 'csp_base.php';
/**
 * A Sudoku solving algorithm that represents the game as a Constraint
 * Satisfaction Problem (CSP) and uses Generalized Arc Consistency to solve the
 * given board.
 *
 * Written by Jacob Mai Peng.
 */
class sudokuAPI extends API
{
  public function __construct($request, $origin) {
    parent::__construct($request);
  }

  /**
   * Health monitor.
   */
  protected function ping() {
    if ($this->method == 'GET') {
      return ['text' => 'Peng'];
    } else {
      return ['error' => 'Only accepts GET requests'];
    }
  }

  /**
   * Solves a sudoku board. Takes 1 parameter, a string representation of the
   * board.
   */
  protected function solve() {
    if ($this->method !== 'POST') {
      return ['error' => 'Only accepts POST requests'];
    } else if (count($this->args) !== 1) {
      return ['error' => 'Must have exactly one parameter'];
    } else if (!is_numeric($this->args[0])) {
      return ['error' => 'Invalid parameter'];
    } else if (strlen($this->args[0]) !== 81) {
      return ['error' => 'Invalid parameter length'];
    }

    // Parse the input parameter.
    $input = str_split($this->args[0]);

    // Populate the board of variables.
    $scope = [];
    $board = [];
    for ($i = 0; $i < 9; $i++) {
      $row = [];
      for ($j = 0; $j < 9; $j++) {
        $val = intval($input[$i][$j]);
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
  }
}

if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
  $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

try {
  $API = new sudokuAPI($_REQUEST['request'], $_SERVER['HTTP_ORIGIN']);
  echo $API->processAPI();
} catch (Exception $e) {
  echo json_encode(Array('error' => $e->getMessage()));
}
?>
