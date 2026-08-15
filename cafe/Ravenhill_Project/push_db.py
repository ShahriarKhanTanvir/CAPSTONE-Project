import pymysql
import os

HOST = 'mehedihasan.au'
USER = 'mehedih3_cpro306_g1'
PASSWORD = 'cpro306'
DATABASE = 'mehedih3_cpro306_g1'

def execute_sql_file(filename):
    print(f"Connecting to MySQL Database {DATABASE} on {HOST}...")
    try:
        connection = pymysql.connect(
            host=HOST,
            user=USER,
            password=PASSWORD,
            database=DATABASE,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        print("Connected successfully!")
        
        with connection.cursor() as cursor:
            with open(filename, 'r') as f:
                sql_file = f.read()
                
            # Split by semicolon and execute one by one
            sql_commands = sql_file.split(';')
            for command in sql_commands:
                if command.strip():
                    try:
                        cursor.execute(command)
                    except Exception as e:
                        print(f"Error executing command: {e}\nCommand: {command}")
                        
        connection.commit()
        print("All SQL commands executed successfully!")
        
    except pymysql.MySQLError as e:
        print(f"Failed to connect to MySQL: {e}")
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

if __name__ == '__main__':
    sql_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'schema.sql')
    execute_sql_file(sql_path)
