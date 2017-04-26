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

  public function testHasSupport()
  {
    $var = $this->c->get_scope()[0];
    $this->assertTrue(
      $this->c->has_support_sudoku($var, 3)
    );
  }
}
?>
