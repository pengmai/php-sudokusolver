<?php
use PHPUnit\Framework\TestCase;

final class ConstraintTest extends TestCase
{
  protected $c;
  protected $row;

  protected function setUp()
  {
    $scope = [];
    for ($i = 0; $i < 3; $i++) {
      $scope[] = new Variable('var' . $i, [1,2,3,4,5]);
    }
    $this->c = new Constraint('con1', $scope);

    $scope = [];
    for ($i = 0; $i < 9; $i++) {
      $scope[] = new Variable('var' . $i, [1,2,3,4,5,6,7,8,9]);
    }

    $this->row = new Constraint('Sudoku', $scope);
  }

  public function testGetScope()
  {
    $names = [];
    foreach ($this->c->get_scope() as $var) {
      $names[] = $var->name();
    }
    $this->assertEquals(
      ['var0', 'var1', 'var2'],
      $names
    );
  }

  public function testGetNUnasgn()
  {
    $this->assertEquals(
      3,
      $this->c->get_n_unasgn()
    );
  }

  public function testGetNUnasgn2()
  {
    $this->c->get_scope()[0]->assign(1);
    $this->assertEquals(
      2,
      $this->c->get_n_unasgn()
    );
  }

  public function testGetUnasgnVars()
  {
    $this->c->get_scope()[0]->assign(1);
    $scopecpy = [];
    for ($i = 1; $i < 3; $i++) {
      $scopecpy[] = new Variable('var' . $i, [1,2,3,4,5]);
    }
    $this->assertEquals(
      $scopecpy,
      $this->c->get_unasgn_vars()
    );
  }

  public function testTupleIsValid()
  {
    $this->assertTrue(
      $this->c->tuple_is_valid([1, 2, 3])
    );
  }

  public function testTupleIsValid2()
  {
    $this->assertFalse(
      $this->c->tuple_is_valid([1, 2, 6])
    );
  }

  public function testTupleIsValid3()
  {
    $this->c->get_scope()[0]->prune_value(1);
    $this->assertFalse(
      $this->c->tuple_is_valid([1, 2, 6])
    );
  }

  public function testTupleIsValid4()
  {
    $this->c->get_scope()[0]->assign(2);
    $this->assertFalse(
      $this->c->tuple_is_valid([1, 2, 6])
    );
  }

  public function testCheckSudoku()
  {
    // Valid board.
    $vals = [2,9,6,3,1,8,5,7,4];
    $this->assertTrue($this->row->check_sudoku($vals));
  }

  public function testCheckSudoku2()
  {
    // Row now valid, duplicate 3.
    $vals = [4,9,6,3,1,8,5,7,3];
    $this->assertFalse($this->row->check_sudoku($vals));
  }

  public function testCheckSudoku3()
  {
    $this->row->get_scope()[1]->assign(8);
    // Valid row, but with an assigned value.
    $vals = [2,9,6,3,1,8,5,7,4];
    $this->assertFalse($this->row->check_sudoku($vals));
  }

  public function testCheckSudoku4()
  {
    $this->row->get_scope()[1]->prune_value(9);
    // Valid row, but with a pruned value.
    $vals = [2,9,6,3,1,8,5,7,4];
    $this->assertFalse($this->row->check_sudoku($vals));
  }

  /**
   * Tests that a constraint with exactly 1 unassigned variable has the support
   * of a value distinct from any of the assigned variables' values.
   */
  public function testHasSupportSuccess()
  {
    for ($i = 1; $i < 3; $i++) {
      $this->c->get_scope()[$i]->assign($i);
    }
    $var = $this->c->get_scope()[0];
    $this->assertTrue($this->c->has_support_sudoku($var, 5));
  }

  /**
   * Tests that a constraint with exactly 1 unassigned variable does not have
   * the support of another (assigned) variable's value.
   */
  public function testHasSupport2()
  {
    for ($i = 1; $i < 3; $i++) {
      $this->c->get_scope()[$i]->assign($i);
    }
    $var = $this->c->get_scope()[0];
    $this->assertFalse(
      $this->c->has_support_sudoku($var, 1)
    );
  }

  /**
   * Tests that an assigned variable doesn't have the support of a different
   * value.
   */
  public function testHasSupportAssignedVariableDifferentValue()
  {
    $var = $this->row->get_scope()[0];
    $var->assign(1);
    $this->assertFalse($this->row->has_support_sudoku($var, 2));
  }

  /**
   * Tests that an assigned variable has the support of its assigned value.
   */
  public function testHasSupportAssignedVariableSameValue()
  {
    $var = $this->c->get_scope()[0];
    $var->assign(1);
    $this->assertTrue($this->c->has_support_sudoku($var, 1));
  }

  public function testHasSupport5()
  {
    $var = $this->c->get_scope()[0];
    $this->c->get_scope()[1]->assign(2);
    $this->c->get_scope()[2]->assign(2);
    $this->assertFalse($this->c->has_support_sudoku($var, 1));
  }

  public function testHasSupportMutating()
  {
    // Tests to ensure that has_support_sudoku is not changing the variable
    // cur_domains.
    $var = $this->c->get_scope()[0];
    $var2 = $this->c->get_scope()[1];

    $var2->assign(1);
    $before1 = $var->cur_domain();
    $before2 = $var2->cur_domain();
    $this->c->has_support_sudoku($var, 1);

    $this->assertEquals($before1, $var->cur_domain());
    $this->assertEquals($before2, $var2->cur_domain());
  }

  public function testHasSupportValidRow()
  {
    $var = $this->row->get_scope()[0];
    $vals = [0, 0, 0, 0, 9, 2, 3, 0, 1];
    for ($i = 1; $i < count($vals); $i++) {
      if ($vals[$i]) {
        $this->row->get_scope()[$i]->assign($vals[$i]);
      }
    }

    $this->assertTrue($this->row->has_support_sudoku($var, 5));
  }

  public function testHasSupportInvalidRow()
  {
    $var = $this->row->get_scope()[0];
    $vals = [0, 0, 0, 0, 9, 2, 2, 0, 1];
    for ($i = 1; $i < count($vals); $i++) {
      if ($vals[$i]) {
        $this->row->get_scope()[$i]->assign($vals[$i]);
      }
    }

    $this->assertFalse($this->row->has_support_sudoku($var, 5));
  }

  public function testHasSupportInvalidValue()
  {
    $var = $this->row->get_scope()[0];
    $vals = [0, 0, 0, 0, 9, 2, 3, 0, 1];
    for ($i = 1; $i < count($vals); $i++) {
      if ($vals[$i]) {
        $this->row->get_scope()[$i]->assign($vals[$i]);
      }
    }

    $this->assertFalse($this->row->has_support_sudoku($var, 9));
  }

  /*public function testDeepCopy()
  {
    $scope = [];
    for ($i = 0; $i < 9; $i++) {
      $scope[] = new Variable('var' . $i, [1,2,3,4,5,6,7,8,9]);
    }
    // Deep copy.
    $scopecpy = [];
    foreach ($scope as $k => $v) {
      $scopecpy[$k] = clone $v;
    }

    $con2 = new Constraint('con2', $scope);
    // Mess with the copy, not the original.
    $scopecpy[0]->prune_value(2);
    $this->assertTrue($con2->check_sudoku([2,9,6,3,1,8,5,7,4]));
  }*/
}
?>
