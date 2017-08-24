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
    }

    // Retrieve the body from the POST request.
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_string($input) && strlen($input) === 81) {
      // Execute the command and convert it into a 2D array.
      $input = trim($input);
      $solution = `./solver.out {$input}`;
      if (is_numeric($solution)) {
        $return = array_chunk(array_map('intval', str_split($solution)), 9);
        return ['solution' => $return];
      } else {
        // Puzzle was unsolved.
        http_response_code(422);
        throw new Exception($solution);
      }
    } else {
      http_response_code(400);
      throw new Exception('Invalid parameter(s)');
    }
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
