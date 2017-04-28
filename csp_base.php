<?php
//require 'minPQ.php';
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
    foreach ($this->scope as $v) {
      if (!$v->is_assigned()) {
        $n++;
      }
    }
    return $n;
  }

  /*
   * Return list of unassigned variables in the constraint's scope.
   */
  public function get_unasgn_vars() {
    $vars = [];
    foreach ($this->scope as $v) {
      if (!$v->is_assigned()) {
        $vars[] = $v;
      }
    }
    return $vars;
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

/**
 * A class for packing up a set of variables into a CSP problem. It contains
 * various utility routines for accessing the problem. The variables of the csp
 * can be added later or on initialization. The constraints must be added later.
 */
class CSP
{
  private $name;
  private $vars;
  private $cons;
  private $vars_to_cons;

  public function __construct ($name, $vars) {
    $this->name = $name;
    $this->vars = [];
    $this->cons = [];
    $this->vars_to_cons = [];
    foreach ($vars as $v) {
      $this->add_var($v);
    }
  }

  /**
   * Add variable object to CSP while setting up an index to obtain the
   * constraints over this variable.
   */
  public function add_var($var) {
    if (!($var instanceof Variable)) {
      throw new Exception ("Trying to add non variable to CSP object");
    } else if (array_key_exists($var->name(), $this->vars_to_cons)) {
      throw new Exception ("Trying to add variable to CSP object that already".
                           " has it");
    } else {
      $this->vars[] = $var;
      $this->vars_to_cons[$var->name()] = [];
    }
  }

  /**
   * Add constraint to CSP. Note that all variables in the scope must already
   * have been added to the CSP.
   */
   public function add_constraint($c) {
     if (!($c instanceof Constraint)) {
       throw new Exception ("Trying to add non constraint to CSP object");
     } else {
       foreach ($c->get_scope() as $v) {
         if (!(in_array($v, $this->vars))) {
           throw new Exception ("Trying to add constraint with unknown " .
                                "variables to CSP object");
         }
         $this->vars_to_cons[$v->name()][] = $c;
       }
       $this->cons[] = $c;
     }
   }

   /**
    * Return the list of all constraints in the CSP.
    */
   public function get_all_cons() {
     return $this->cons;
   }

   /**
    * Return the list of constraints that include var in their scope.
    */
   public function get_all_cons_with_var($var) {
     return $this->vars_to_cons[$var->name()];
   }

   /**
    * Return the list of variables in the CSP.
    */
   public function get_all_vars() {
     return $this->vars;
   }
}

/**
 * Class to encapsulate things like statistics and bookkeeping for pruning
 * variable domains.
 */
class BT
{
  private $csp;
  private $nDecisions;
  private $nPrunings;
  private $unasgn_vars;
  private $TRACE;
  private $runtime;

  public function __construct($csp) {
    $this->csp = $csp;
    $this->nDecisions = 0; // Number of variable assignments made during search.
    $this->nPrunings = 0; // Number of value prunings during search.
    $this->unasgn_vars = [];
    $this->TRACE = False;
    $this->runtime = 0;
  }

  /**
   * Turn search trace on.
   */
  public function trace_on() {
    $this->TRACE = True;
  }

  /**
   * Turn search trace off.
   */
  public function trace_off() {
    $this->TRACE = False;
  }

  /**
   * Initialize counters.
   */
  public function clear_stats() {
    $this->nDecisions = 0;
    $this->nPrunings = 0;
    $this->runtime = 0;
  }

  public function print_stats() {
    print ("Search made {$nDecisions} variable assignments and pruned " .
           "{$nPrunings} variable values.");
  }

  /**
   * Restore a list of values to variable domains. Each item in prunings goes
   * value => variable.
   */
  public function restore_values($prunings) {
    foreach ($prunings as $val => $var) {
      $var->unprune_value($val);
    }
  }

  /**
   * Reinitialize all variable domains.
   */
  public function restore_all_variable_domains() {
    foreach ($this->csp->get_all_vars() as $var) {
      if ($var->is_assigned()) {
        $var->unassign();
      }
      $var->restore_curdom();
    }
  }

  /**
   * Remove variable with the smallest current domain from the list of
   * unassigned variables.
   */
  public function extract_mrv_var() {
    $max_d = PHP_INT_MAX;
    $max_v;
    $mk;

    foreach ($this->unasgn_vars as $k => $var) {
      if ($var->cur_domain_size() < $max_d) {
        $max_d = $var->cur_domain_size();
        $max_v = $var;
        $mk = $k;
      }
    }
    unset($this->unasgn_vars[$k]);
    return $max_v;
  }

  /**
   * Add variable back to the list of unassigned variables.
   */
  public function restore_unasgn_var($var) {
    $this->unasgn_vars[] = $var;
  }

  public function bt_search() {
    $this->clear_stats();
    //do time stuff here

    $this->restore_all_variable_domains();
    $this->unasgn_vars = [];
    foreach ($this->csp->get_all_vars() as $v) {
      if (!$v->is_assigned()) {
        $this->unasgn_vars[] = $var;
      }
    }
  }
}
?>
