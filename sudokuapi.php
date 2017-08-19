<?php
require_once 'api.class.php';

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
      http_response_code(405);
      throw new Exception('Only accepts GET requests');
    }
  }

  /**
   * Solves a sudoku board. Takes 1 parameter, a string representation of the
   * board.
   */
  protected function solve() {
    if ($this->method !== 'POST') {
      http_response_code(405);
      throw new Exception('Only accepts POST requests');
    // } else if (!array_key_exists('Authorization', $this->request)) {
    //   http_response_code(401);
    //   throw new Exception('Authorization failed');
    } else if (count($this->args) !== 1) {
      http_response_code(400);
      throw new Exception('Invalid parameter(s)');
    } else if (strlen($this->args[0]) !== 81) {
      http_response_code(400);
      throw new Exception('Invalid parameter(s)');
    }

    // Execute the command and convert it into a 2D array.
    // TODO: Add proper error handling.
    $input = trim($this->args[0]);
    $solution = `./solver.out {$input}`;
    if ($solution === 'Error: Puzzle cannot be solved.') {
      http_response_code(422);
      return ['error' => 'Puzzle cannot be solved.'];
    }
    $return = array_chunk(array_map('intval', str_split($solution)), 9);
    return ['solution' => $return];
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
