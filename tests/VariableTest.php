<?php
use PHPUnit\Framework\TestCase;

final class VariableTest extends TestCase
{
  public function testDomain()
  {
    $domain = [1, 2, 3, 4, 5, 6, 7, 8, 9];
    $var = new Variable('var1', $domain);
    $this->assertEquals(
      [1, 2, 3, 4, 5, 6, 7, 8, 9],
      $var->domain()
    );
  }

  public function testAddToDomain()
  {
    $domain = [1, 2, 3];
    $var = new Variable('var1', $domain);
    $var->add_domain_values([4, 5, 6]);
    $this->assertEquals(
      [1, 2, 3, 4, 5, 6],
      $var->domain()
    );
  }

  public function testDomainSize()
  {
    $var = new Variable('var1', [1, 2, 3, 4, 5, 6, 7, 8, 9]);
    $this->assertEquals(9, $var->domain_size());
  }

  public function testPruneValue()
  {
    $var = new Variable('var1', ['one', 'two', 'three']);
    $var->prune_value('one');
    $var->prune_value('three');
    $var->unprune_value('three');
    /*$this->assertEquals(
      [1 => 'two', 2 => 'three'],
      $var->cur_domain()
    );*/
    $this->assertEquals(
      [0, 1, 1],
      $var->curdom()
    );
  }

  public function testCurDomain()
  {
    $var = new Variable('var1', ['one', 'two', 'three']);
    $var->prune_value('one');
    $var->prune_value('three');
    $var->unprune_value('three');
    $this->assertEquals(
      ['two', 'three'],
      $var->cur_domain()
    );
  }

  public function testInCurDomain()
  {
    $var = new Variable('var1', ['one', 'two', 'three']);
    $this->assertTrue($var->in_cur_domain('two'));
  }

  public function testInCurDomain2()
  {
    $var = new Variable('var1', ['one', 'two', 'three']);
    $var->prune_value('two');
    $this->assertFalse($var->in_cur_domain('two'));
  }

  public function testInCurDomain3()
  {
    $var = new Variable('var1', ['one', 'two', 'three']);
    $this->assertFalse($var->in_cur_domain('four'));
  }

  public function testInCurDomain4()
  {
    $var = new Variable('var1', ['one', 'two', 'three']);
    $var->assign('one');
    $this->assertFalse($var->in_cur_domain('two'));
    $this->assertTrue($var->in_cur_domain('one'));
  }

  public function testCurDomainSize()
  {
    $var = new Variable('var1', [1, 2, 3, 4, 5, 6, 7, 8, 9]);
    $var->prune_value(1);
    $var->prune_value(7);
    $this->assertEquals(7, $var->cur_domain_size());
  }

  public function testCurDomainSize2()
  {
    $var = new Variable('var1', [1, 2, 3, 4, 5, 6]);
    $var->assign(1);
    $this->assertEquals(1, $var->cur_domain_size());
  }

  public function testRestoreCurdom()
  {
    $var = new Variable('var1', ['one', 'two', 'three']);
    foreach($var->domain() as $val) {
      $var->prune_value($val);
    }
    $this->assertEquals([], $var->cur_domain());
    $var->restore_curdom();
    $this->assertEquals(
      ['one', 'two', 'three'],
      $var->cur_domain()
    );
  }

  public function testAssign()
  {
    $this->expectExceptionMessage("Trying to assign variable that is already " .
                                  "assigned or illegal (not in curdom)");
    // Cannot assign value outside of domain.
    $var = new Variable('var1', ['one', 'two', 'three']);
    $var->assign('four');
  }

  public function testAssign2()
  {
    $this->expectExceptionMessage("Trying to assign variable that is already " .
                                  "assigned or illegal (not in curdom)");
    $var = new Variable('var1', ['one', 'two', 'three']);
    // Variable already assigned.
    $var->assign('one');
    $var->assign('one');
  }

  public function testAssign3()
  {
    $this->expectExceptionMessage("Trying to assign variable that is already " .
                                  "assigned or illegal (not in curdom)");
    $var = new Variable('var1', ['one', 'two', 'three']);
    // Variable already assigned.
    $var->assign('one');
    $var->assign('two');
  }

  public function testUnassign()
  {
    // Cannot unassign a variable that has not been assigned.
    $this->expectExceptionMessage("Trying to unassign variable that is not " .
                                  "assigned");
    $var = new Variable('var1', [1, 2, 3, 4, 5, 6, 7, 8, 9]);
    $var->unassign();
  }

  public function testValueIndex()
  {
    $var = new Variable('var1', [1, 2, 3, 4, 5, 6, 7, 8, 9]);
    $this->assertEquals(3, $var->value_index(4));
  }
}
?>
