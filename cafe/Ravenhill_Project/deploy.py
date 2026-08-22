import os
import sys
import paramiko
import stat

# SFTP Configuration
HOST = '116.255.43.78' # mehedihasan.au
PORT = 2222
USERNAME = 'mehedih3_cpro306_g1'
PASSWORD = 'cpro306'
REMOTE_PATH = '.'
LOCAL_DIR = os.path.dirname(os.path.abspath(__file__))

# Files/Dirs to ignore
IGNORE_LIST = ['.git', '.DS_Store', 'node_modules', 'deploy.py', 'ravenhill.db']

def is_ignored(path):
    for ignore in IGNORE_LIST:
        if ignore in path:
            return True
    return False

def sftp_upload_dir(sftp, local_dir, remote_dir):
    try:
        try:
            sftp.stat(remote_dir)
        except IOError:
            print(f"Creating remote directory: {remote_dir}")
            sftp.mkdir(remote_dir)
            
        for item in os.listdir(local_dir):
            local_path = os.path.join(local_dir, item)
            remote_path = f"{remote_dir}/{item}".replace('//', '/')
            
            if is_ignored(local_path):
                continue
                
            if os.path.isfile(local_path):
                print(f"Uploading file: {local_path} -> {remote_path}")
                sftp.put(local_path, remote_path)
            elif os.path.isdir(local_path):
                sftp_upload_dir(sftp, local_path, remote_path)
    except Exception as e:
        print(f"Error during upload: {e}")

def main():
    print("=== Ravenhill Project SFTP Deployment ===")
    
    if len(sys.argv) > 1 and sys.argv[1] == '--auto':
        print("Starting auto-deployment...")
    else:
        confirm = input(f"Deploy to {HOST}:{PORT}{REMOTE_PATH}? (y/n): ")
        if confirm.lower() != 'y':
            print("Deployment cancelled.")
            return

    try:
        transport = paramiko.Transport((HOST, PORT))
        transport.connect(username=USERNAME, password=PASSWORD)
        
        sftp = paramiko.SFTPClient.from_transport(transport)
        
        print(f"Connected to {HOST}:{PORT}")
        print(f"Starting upload from {LOCAL_DIR} to {REMOTE_PATH}")
        
        sftp_upload_dir(sftp, LOCAL_DIR, REMOTE_PATH)
        
        sftp.close()
        transport.close()
        print("Deployment completed successfully!")
        
    except Exception as e:
        print(f"Connection failed: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
