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
#include <cerrno>
#include <signal.h>
#include <unistd.h>
#include <sys/wait.h>
#include <sys/types.h>
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
    
    // Run the solving algorithm in a child process, set a timeout in the parent.
    int pipefd[2];
    int buffer[9][9];
    sigset_t mask;
    sigset_t orig_mask;
    struct timespec timeout;
    
    sigemptyset(&mask);
    sigaddset(&mask, SIGCHLD);
    
    // Prevent race conditions by blocking SIGCHLD before fork().
    if (sigprocmask(SIG_BLOCK, &mask, &orig_mask) < 0) {
        cout << "Signal blocking failed.";
        return 1;
    }
    
    if (pipe(pipefd) == -1) {
        cout << "Pipe failed.";
        return 1;
    }
    
    pid_t pid = fork();
    if (pid == 0) {
        // Child process. Close unused read end.
        close(pipefd[0]);
        
        SudokuSolver SS(problemMatrix);
        
        write(pipefd[1], problemMatrix, 9 * (9 * sizeof(int)));
        
        // Reader will see EOF.
        close(pipefd[1]);
    } else if (pid < 0) {
        cout << "Fork failed.";
        return -1;
    } else {
        // Parent process.
        close(pipefd[1]);
        timeout.tv_sec = 5;
        timeout.tv_nsec = 0;
        
        while (1) {
            if (sigtimedwait(&mask, NULL, &timeout) < 0) {
                if (errno == EINTR) {
                    // Interrupted by a signal other than SIGCHLD.
                    continue;
                } else if (errno == EAGAIN) {
                    cout << "Solver timed out.";
                    kill (pid, SIGKILL);
                    return 1;
                } else {
                    cout << "Error with sigtimedwait";
                    return 1;
                }
            }
            
            break;
        }
        
        int status;
        if (waitpid(pid, &status, 0) < 0) {
            cout << "Error with waitpid";
            return 1;
        }
        
        if (!WIFEXITED(status) || WEXITSTATUS(status) != 0) {
            cout << "Puzzle cannot be solved.";
            return 1;
        }
        
        if (read(pipefd[0], &buffer, 9 * (9 * sizeof(int))) < 0) {
            cout << "Problem reading from solver";
            return 1;
        }
        
        close(pipefd[0]);
        
        // Format the solution into a single string.
        ostringstream os;
        for (int row = 0; row < 9; row++) {
            for (int col = 0; col < 9; col++) {
                os << buffer[row][col];
            }
        }
        cout << os.str();
    }

    return 0;
}

void initPuzzle (int (&problemMatrix) [9][9], char *puzzle) {
    for (int i = 0; i < 9; i++)
        for (int j = 0; j < 9; j++) {
            int val = puzzle[i * 9 + j] - '0';
            problemMatrix [i][j] = (0 < val && val < 10) ? val : 0;
        }
}
