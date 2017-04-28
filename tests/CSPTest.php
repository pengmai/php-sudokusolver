<?php
use PHPUnit\Framework\TestCase;

final class CSPTest extends TestCase
{
  protected $csp;
  protected $vars;

  protected function setUp()
  {
    $this->vars = [];
    for($i = 0; $i < 9; $i++) {
      $this->vars[] = new Variable("var{$i}", [1,2,3,4,5,6,7,8,9]);
    }
    $this->csp = new CSP('myCSP', $this->vars);
  }

  public function testAddVar()
  {
    $var = new Variable("var9", [1,2,3,4,5,6,7,8,9]);
    $this->csp->add_var($var);
    $this->assertEquals(
      $var,
      $this->csp->get_all_vars()[9]
    );
  }

  public function testAddVarWithInvalidInput()
  {
    $this->expectExceptionMessage("Trying to add non variable to CSP object");
    $var = [1, 2, 3];
    $this->csp->add_var($var);
  }

  public function testAddVarAlreadyAdded()
  {
    $this->expectExceptionMessage("Trying to add variable to CSP object that " .
                                  "already has it");
    $var = $this->vars[0];
    $this->csp->add_var($var);
  }

  public function testAddConstraintValid()
  {
    $con = new Constraint('con1', $this->vars);
    $this->csp->add_constraint($con);
    $this->assertEquals(
      [$con],
      $this->csp->get_all_cons()
    );
  }

  public function testAddConstraintWithInvalidInput()
  {
    $this->expectExceptionMessage("Trying to add non constraint to CSP object");
    $con = [];
    $this->csp->add_constraint($con);
  }

  public function testAddConstraintWithUnknownVariable()
  {
    $this->expectExceptionMessage("Trying to add constraint with unknown " .
                                  "variables to CSP object");
    $scope = $this->vars;
    $scope[] = new Variable('varx', [1, 2, 3]);
    $con = new Constraint('con1', $scope);
    $this->csp->add_constraint($con);
  }

  public function testGetAllConsWithVar()
  {
    $var1 = $this->vars[1];
    $var2 = $this->vars[2];
    $con1 = new Constraint('con1', [$var1]);
    $con2 = new Constraint('con2', [$var2]);

    $this->csp->add_constraint($con1);
    $this->csp->add_constraint($con2);

    $this->assertEquals(
      [$con1],
      $this->csp->get_all_cons_with_var($var1)
    );
  }
}
?>
