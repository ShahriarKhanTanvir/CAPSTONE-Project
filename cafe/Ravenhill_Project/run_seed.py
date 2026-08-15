import paramiko

HOST = 'mehedihasan.au'
PORT = 2222
USERNAME = 'mehedih3_cpro306_g1'
PASSWORD = 'cpro306'

def run_remote_seed():
    try:
        client = paramiko.SSHClient()
        client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
        client.connect(hostname=HOST, port=PORT, username=USERNAME, password=PASSWORD)
        
        print("Connected to SSH. Running seed_menu.php...")
        stdin, stdout, stderr = client.exec_command('php ./seed_menu.php')
        
        out = stdout.read().decode()
        err = stderr.read().decode()
        
        print("STDOUT:")
        print(out)
        print("STDERR:")
        print(err)
        
        client.close()
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    run_remote_seed()
