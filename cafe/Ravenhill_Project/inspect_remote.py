import paramiko
import stat

HOST = 'mehedihasan.au'
PORT = 2222
USERNAME = 'mehedih3_cpro306_g1'
PASSWORD = 'cpro306'

def inspect_server():
    transport = paramiko.Transport((HOST, PORT))
    transport.connect(username=USERNAME, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    print("\n=== Listing './api' ===")
    for attr in sftp.listdir_attr('./api'):
        is_dir = stat.S_ISDIR(attr.st_mode)
        print(f"{'[DIR] ' if is_dir else '[FILE]'} {attr.filename}")

    print("\n=== Listing './api/menu' ===")
    for attr in sftp.listdir_attr('./api/menu'):
        is_dir = stat.S_ISDIR(attr.st_mode)
        print(f"{'[DIR] ' if is_dir else '[FILE]'} {attr.filename}")

    sftp.close()
    transport.close()

if __name__ == '__main__':
    inspect_server()
