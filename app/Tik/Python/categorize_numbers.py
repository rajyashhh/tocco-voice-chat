# categorize_numbers.py

import sys
import numpy as np
import json


def categorize_numbers(numbers):
    # Calculate percentiles
    threshold1 = np.percentile(numbers, 33.33)
    threshold2 = np.percentile(numbers, 66.67)

    return {
        'threshold1': threshold1,
        'threshold2': threshold2,
    }

if __name__ == "__main__":
    # Example usage:
    input_numbers = list(map(float, sys.argv[1:]))
    result = categorize_numbers(input_numbers)
    print(json.dumps(result))
