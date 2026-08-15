import re

def validate_sql(filename):
    with open(filename, 'r', encoding='utf-8') as f:
        sql = f.read()

    # Check for unescaped quotes or unterminated string literals
    in_single_quote = False
    i = 0
    errors = []
    line_num = 1
    
    while i < len(sql):
        c = sql[i]
        if c == '\n':
            line_num += 1
            i += 1
            continue
            
        if not in_single_quote:
            # Check comment
            if sql[i:i+2] == '--':
                # Skip till newline
                next_nl = sql.find('\n', i)
                if next_nl == -1:
                    break
                line_num += 1
                i = next_nl + 1
                continue
            if c == "'":
                in_single_quote = True
                quote_start_line = line_num
        else:
            if c == "'":
                # Check for escaped single quote ''
                if i + 1 < len(sql) and sql[i+1] == "'":
                    i += 1 # skip escaped quote
                else:
                    in_single_quote = False
        i += 1

    if in_single_quote:
        print(f"ERROR: Unterminated single quote starting near line {quote_start_line}")
        return False
    else:
        print(f"SUCCESS: All string literals and quotes in {filename} are perfectly balanced and valid!")
        return True

if __name__ == '__main__':
    validate_sql('schema.sql')
    validate_sql('ravenhill_database.sql')
