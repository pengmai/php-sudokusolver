import datetime, subprocess

if __name__ == '__main__':
    print("Starting script")
    print("-" * 80)
    total_time = 0
    most_time = -1
    hardest_puzzle = ''
    tests_passed = 0
    tests_failed = 0
    unsolved_puzzles = []
    with open('input.txt') as f:
        for line in f:
            try:
                line = line.strip()
                start_time = datetime.datetime.now()
                subprocess.run(['./solver.out', line], timeout=5)
                tests_passed += 1
                delta_time = (datetime.datetime.now() - start_time).total_seconds()
                total_time += delta_time
                if delta_time > most_time:
                    most_time = delta_time
                    hardest_puzzle = line
            except subprocess.TimeoutExpired:
                tests_failed += 1
                unsolved_puzzles.append(line)
    print("\n", "-" * 80)
    print("Finished.")
    print("Number of puzzles solved:", tests_passed)
    print("Number of puzzles unsolved:", tests_failed)
    print("Longest time taken:", most_time)
    print("Mean time:", total_time / tests_passed)
    print("Hardest (solved) puzzle:", hardest_puzzle)
    print("Unsolved puzzle(s):", unsolved_puzzles)
