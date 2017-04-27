<?php
/**
  * A class for defining CSP variables.
  */
class Variable
{
  private $name;
  private $dom;
  private $curdom;
  private $assignedValue;

  public function __construct($name, $domain) {
    $this->name = $name;
    $this->dom = $domain;
    $this->curdom = array_fill(0, count($domain), True);
    $this->assignedValue = NULL;
  }
  /**
    * Add additional domain values to the domain. Removals not supported.
    */
  public function add_domain_values($values) {
    foreach ($values as $value) {
      $this->dom[] = $value;
    }
  }

  /**
    * Return the size of the permanent domain.
    */
  public function domain_size() {
    return count($this->dom);
  }

  /**
    * Return the variable's permanent domain.
    */
  public function domain() {
    return $this->dom;
  }

  /**
    * Remove the value from the current domain.
    */
  public function prune_value($value) {
    $this->curdom[$this->value_index($value)] = False;
  }

  /**
    * Add the value back to the current domain.
    */
  public function unprune_value($value) {
    $this->curdom[$this->value_index($value)] = True;
  }

  /**
    * Returns the internal curdom bookkeeping array. Purely for testing.
    */
  public function curdom() {
    return $this->curdom;
  }

  /**
    * Return a list of the values in the current domain.
    */
  public function cur_domain() {
    $vals = [];
    if ($this->is_assigned()) {
      $vals[] = $this->get_assigned_value();
    } else {
      for ($i = 0; $i < count($this->dom); $i++) {
        if ($this->curdom[$i]) {
          $vals[] = $this->dom[$i];
        }
      }
    }

    return $vals;
  }

  /**
    * Check if value is in the current domain.
    */
  public function in_cur_domain($value) {
    if (!in_array($value, $this->dom)) {
      return False;
    } else if ($this->is_assigned()) {
      return $value === $this->get_assigned_value();
    }
    return $this->curdom[$this->value_index($value)];
  }

  public function cur_domain_size() {
    if ($this->is_assigned()) {
      return 1;
    }
    return array_sum($this->curdom);
  }

  public function restore_curdom() {
    for ($i = 0; $i < count($this->curdom); $i++) {
      $this->curdom[$i] = True;
    }
  }

  public function is_assigned() {
    return $this->assignedValue != NULL;
  }

  /**
    * Used by bt search. When we assign we remove all other values from curdom.
    * This information is saved so we can reverse it on unassign.
    */
  public function assign($value) {
    if ($this->is_assigned() || !($this->in_cur_domain($value))) {
      throw new Exception("Trying to assign variable that is already assigned
                           or illegal (not in curdom)");
    }
    $this->assignedValue = $value;
  }

  public function unassign() {
    if (!($this->is_assigned())) {
      throw new Exception("Trying to unassign variable that is not assigned");
    }
    $this->assignedValue = NULL;
  }

  public function get_assigned_value() {
    return $this->assignedValue;
  }

  /* Internal methods. */
  /**
    * Return the index of the given value in the domain. Public for the sake of
    * testing.
    */
  public function value_index($value) {
    return array_search($value, $this->dom);
  }

  public function name() {
    return $this->name;
  }
}

/**
  * A class for defining constraints over variable objects.
  */
class Constraint
{
  private $name;
  private $scope;

  public function __construct($name, $scope) {
    $this->name = $name;
    $this->scope = $scope;
  }

  /**
    * Return a list of variables the constraint is over.
    */
  public function get_scope() {
    return $this->scope;
  }

  /**
    * Given a list of values, one for each variable in the constraints scope,
    * return true iff these value assignments satisfy the constraint by applying
    * the constraints "satisfies" function. The array of values must be ordered
    * in the same order as the list of variables in the constraints scope.
    *
    * Specific to Sudoku.
    */
  public function check_sudoku($vals) {
    for ($i = 0; $i < count($vals); $i++) {
      if (!in_array($vals[$i], $this->scope[$i]->cur_domain())) {
        return False;
      }
    }

    return $this->sudoku_ok($vals);
    /*$matrix = array_chunk($vals, 9);

    // Check rows.
    foreach ($matrix as $row) {
      if (!$this->sudoku_ok($row)) {
        return False;
      }
    }
    // Check columns.
    for ($i = 0; $i < count($matrix); $i++) {
      if (!$this->sudoku_ok(array_column($matrix, $i))) {
        return False;
      }
    }
    // Check squares.
    for ($i = 0; $i < count($matrix); $i++) {
      $xStart = floor($i / 3) * 3;
      $yStart = ($i % 3) * 3;
      $line = [];

      for ($x = $xStart; $x < $xStart + 3; $x++) {
        for ($y = $yStart; $y < $yStart + 3; $y++) {
          $line[] = $matrix[$x][$y];
        }
      }
      if (!$this->sudoku_ok($line)) {
        return False;
      }
    }
    return True;*/
  }

  private function sudoku_ok($line) {
    return (count($line) == 9 &&
            array_sum($line) === array_sum(array_unique($line)));
  }

  /**
    * Return the number of unassigned variables in the constraint.
    */
  public function get_n_unasgn() {
    $n = 0;
    foreach($this->scope as $v) {
      if (!$v->is_assigned()) {
        $n++;
      }
    }
    return $n;
  }

  /**
    * Test if a variable value pair has a supporting tuple (a set of assignments
    * satisfying the constraint where each value is still in the corresponding
    * variables current domain)
    *
    * Specific to sudoku, checks if a partial board can be solved.
    */
  public function has_support_sudoku($var, $val) {
    if (in_array($var, $this->scope) && $var->in_cur_domain($val)) {
      // Store each domain of each variable in a 2D array.
      // Set given variable and any other set variables.
      // Prune values from the remaining domains until there are no variables
      // left to set.
      // If any of the domains are empty, DWO. Otherwise, return True.

      // Store each domain of each variable in a 2D array, pruning the entered
      // value as it's copied.
      $domains = [];
      foreach ($this->scope as $k => $v) {
        if ($var !== $k) {
          $domains[] = array_diff($v->cur_domain(), [$val]);
        }
      }

      // Prune values from the remaining domains until there are no variables
      // left to set.
      $can_prune = True;
      while ($can_prune) {
        $can_prune = False;
        // Prune the values of the assigned variables.
        foreach($domains as $k => $dom) {
          if (empty($dom)) {
            return False; // Domain Wipe Out
          } else if (count($dom) === 1) {
            // Remove it from the current domains of all unassigned variables.
            $value = $dom[0];
            unset($domains[$k]);
            foreach($domains as $w) {
              if (in_array($value, $w)) {
                unset($w[array_search($value, $w)]);
                if (empty($w)) {
                  return False; // Domain Wipe Out
                }
              }
            }
          }
        }

        // Check to see if further pruning can occur.
        foreach($domains as $w) {
          if (count($w) === 1) {
            $can_prune = True;
            break;
          }
        }

        // Assign variables with only one possible value left and check if
        // further pruning can be done.
        /*$can_prune = False;
        foreach($vars as $v) {
          if ($v->cur_domain_size() === 1 && !$v->is_assigned()) {
            $can_prune = True;
            $v->assign($v->cur_domain()[0]);
          }
        }*/
      }

      return True;
    }
    return False;
  }

  /**
    * Internal routine. Check if every value in tuple is still in the
    * corresponding variable domain.
    */
  public function tuple_is_valid($t) {
    for ($i = 0; $i < count($t); $i++) {
      if (!$this->scope[$i]->in_cur_domain($t[$i])) {
        return False;
      }
    }
    return True;
  }
}
?>
