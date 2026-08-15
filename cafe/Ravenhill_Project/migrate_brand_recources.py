import os
import shutil
import re
import paramiko

def migrate_local_files():
    # Create brand_recources directory
    os.makedirs('brand_recources', exist_ok=True)
    os.makedirs('brand_recources/uploads', exist_ok=True)
    
    # Copy all images to brand_recources
    src_dir = 'Brand Resources'
    if os.path.exists(src_dir):
        for f in os.listdir(src_dir):
            src_path = os.path.join(src_dir, f)
            if os.path.isfile(src_path):
                shutil.copy2(src_path, os.path.join('brand_recources', f))
                print(f"Copied {f} to brand_recources/")

    # Update index.php
    if os.path.exists('index.php'):
        with open('index.php', 'r', encoding='utf-8') as f:
            content = f.read()
        content = content.replace('Brand Resources', 'brand_recources')
        content = content.replace('Brand%20Resources', 'brand_recources')
        with open('index.php', 'w', encoding='utf-8') as f:
            f.write(content)
        print("Updated index.php to use brand_recources/")

    # Update app.js
    if os.path.exists('app.js'):
        with open('app.js', 'r', encoding='utf-8') as f:
            content = f.read()
        content = content.replace('Brand%20Resources/', 'brand_recources/')
        content = content.replace('Brand Resources/', 'brand_recources/')
        content = content.replace('Brand%20Resources', 'brand_recources')
        content = content.replace('Brand Resources', 'brand_recources')
        with open('app.js', 'w', encoding='utf-8') as f:
            f.write(content)
        print("Updated app.js to use brand_recources/")

    # Update api/utils/upload.php
    upload_php = 'api/utils/upload.php'
    if os.path.exists(upload_php):
        with open(upload_php, 'r', encoding='utf-8') as f:
            content = f.read()
        content = content.replace('Brand Resources', 'brand_recources')
        content = content.replace('Brand%20Resources', 'brand_recources')
        with open(upload_php, 'w', encoding='utf-8') as f:
            f.write(content)
        print("Updated upload.php to use brand_recources/")

def upload_to_remote_sftp():
    HOST = 'mehedihasan.au'
    PORT = 2222
    USERNAME = 'mehedih3_cpro306_g1'
    PASSWORD = 'cpro306'
    
    transport = paramiko.Transport((HOST, PORT))
    transport.connect(username=USERNAME, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    # Create brand_recources dir on remote if not exists
    try:
        sftp.mkdir('brand_recources')
        print("Created remote directory: brand_recources")
    except:
        print("Remote directory brand_recources already exists.")
        
    try:
        sftp.mkdir('brand_recources/uploads')
        print("Created remote directory: brand_recources/uploads")
    except:
        pass

    # Upload all files in brand_recources to remote
    for f in os.listdir('brand_recources'):
        local_f = os.path.join('brand_recources', f)
        if os.path.isfile(local_f):
            remote_f = f'brand_recources/{f}'
            print(f"Uploading {local_f} -> {remote_f}")
            sftp.put(local_f, remote_f)

    # Upload updated index.php and app.js
    sftp.put('index.php', 'index.php')
    sftp.put('app.js', 'app.js')
    sftp.put('api/utils/upload.php', 'api/utils/upload.php')
    print("Uploaded updated index.php, app.js, and upload.php!")

    sftp.close()
    transport.close()
    print("SFTP migration to brand_recources complete!")

if __name__ == '__main__':
    migrate_local_files()
    upload_to_remote_sftp()
