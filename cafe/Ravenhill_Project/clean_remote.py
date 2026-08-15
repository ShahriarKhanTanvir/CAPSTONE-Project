import paramiko
import sys

HOST = 'mehedihasan.au'
PORT = 2222
USERNAME = 'mehedih3_cpro306_g1'
PASSWORD = 'cpro306'
REMOTE_PATH = '.'

def clean_remote_html():
    try:
        transport = paramiko.Transport((HOST, PORT))
        transport.connect(username=USERNAME, password=PASSWORD)
        sftp = paramiko.SFTPClient.from_transport(transport)
        
        def remove_html_files(remote_dir):
            for entry in sftp.listdir_attr(remote_dir):
                path = remote_dir + '/' + entry.filename
                if paramiko.sftp_client.stat.S_ISDIR(entry.st_mode):
                    pass # Only remove html files in the root or recursively? Let's just do root first
                elif entry.filename.endswith('.html'):
                    print(f"Removing remote file: {path}")
                    sftp.remove(path)
                    
        remove_html_files(REMOTE_PATH)
        print("Cleaned remote HTML files.")
        sftp.close()
        transport.close()
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    clean_remote_html()
