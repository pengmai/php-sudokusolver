<?php
use PHPUnit\Framework\TestCase;

final class BTTest extends TestCase
{
  /*protected function setUp()
  {

  }*/
  public function testExtractMRV()
  {
    $var1 = new Variable('var1', [1, 2, 3]);
    $var2 = new Variable('var2', [1, 2]);
    $csp = new CSP('csp', [$var1, $var2]);
    $bt = new BT($csp);
    $bt->restore_unasgn_var($var1);
    $bt->restore_unasgn_var($var2);
    $var1->prune_value(1);
    $var1->prune_value(2);
    $this->assertEquals($var1, $bt->extract_mrv_var());
  }

  /**
   * Tests that prop_GAC properly detects when a simple CSP cannot be solved.
   */
  public function testPropGACInvalid()
  {
    $var1 = new Variable('var1', [1]);
    $var2 = new Variable('var2', [1]);
    $con = new Constraint('con', [$var1, $var2]);
    $csp = new CSP("csp", [$var1, $var2]);
    $csp->add_constraint($con);
    $bt = new BT($csp);
    $this->assertFalse($bt->prop_GAC()[0]);
  }

  /**
   * Tests that prop_GAC properly detects when a simple CSP can be solved.
   */
  public function testPropGACValid()
  {
    $var1 = new Variable('var1', [1]);
    $var2 = new Variable('var2', [2]);
    $con = new Constraint('con', [$var1, $var2]);
    $csp = new CSP("csp", [$var1, $var2]);
    $csp->add_constraint($con);
    $bt = new BT($csp);
    $this->assertTrue($bt->prop_GAC()[0]);
  }

  /**
   * Tests backtracking search on a simple CSP.
   */
  public function testSimpleCSP()
  {
    $var1 = new Variable('var1', [1, 2, 3]);
    $var2 = new Variable('var2', [1, 2, 3]);
    $var3 = new Variable('var3', [1, 2, 3]);
    $con = new Constraint('simple_con', [$var1, $var2, $var3]);
    $csp = new CSP('CSP', [$var1, $var2, $var3]);
    $csp->add_constraint($con);
    $bt = new BT($csp);
    $bt->bt_search();
  }
}
?>
