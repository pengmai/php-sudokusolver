/*
 ******************************************************************************
 *  fileName    :   main.cc
 *
 *  Author      :   Aditya Shevade <aditya.shevade@gmail.com>
 *  Version     :   1.0.1
 *
 *  Created     :   09/04/2011
 *  Modified    :   12/06/2011
 *
 *  Description :   This code reads a text file in a specific format and then
 *                  populates a 2D array. Then it creates an object of the
 *                  SudokuSolver class. That class takes in the puzzle matrix
 *                  and returns the solved puzzle. Finally the result is written
 *                  back to another file.
 *
 *  License     :   This program is free software: you can redistribute it and/or modify
 *                  it under the terms of the GNU General Public License as published by
 *                  the Free Software Foundation, either version 3 of the License, or
 *                  (at your option) any later version.
 *
 *                  This program is distributed in the hope that it will be useful,
 *                  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *                  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *                  GNU General Public License for more details.
 *
 *                  You should have received a copy of the GNU General Public License
 *                  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Notes       :   Input file format:
 *                  row column value
 *                  row column value
 *                  ...
 *
 *  Changelog   :
 *      12/06/2011  :   Added license.
 *
 *
 ******************************************************************************
 */

#include <iostream>
#include <cstdlib>
#include <sstream>
#include <string>
#include "SudokuSolver.cc"  // The SudokuSolver class.

using namespace std;

void initPuzzle (int (&problemMatrix) [9][9], char *puzzle);

int main (int argc, char *argv[]) {
    int row, col, value;
    int problemMatrix [9][9];

    if (argc != 2) {
        cerr << "Error: Usage: " << argv[0] << " <Puzzle>" << endl;
        cerr << "Any combination of '0' and '.' can be used to signify blank lines" << endl;
        exit(1);
    } else if (strlen(argv[1]) != 81) {
        cerr << "Error: Invalid parameter length" << endl;
        exit(1);
    }

    initPuzzle (problemMatrix, argv[1]);
    SudokuSolver SS(problemMatrix);

    // Format the solution into a single string.
    ostringstream os;
    for (int row = 0; row < 9; row++) {
        for (int col = 0; col < 9; col++) {
          os << problemMatrix[row][col];
        }
    }
    cout << os.str();

    return 0;
}

void initPuzzle (int (&problemMatrix) [9][9], char *puzzle) {
    for (int i = 0; i < 9; i++)
        for (int j = 0; j < 9; j++) {
            int val = puzzle[i * 9 + j] - '0';
            problemMatrix [i][j] = (0 < val && val < 10) ? val : 0;
        }
}
