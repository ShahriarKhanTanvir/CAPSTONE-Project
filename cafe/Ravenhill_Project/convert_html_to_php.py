import os
import paramiko

def convert_html_to_php():
    with open('index.html', 'r', encoding='utf-8') as f:
        html = f.read()

    # PHP header with session & CSRF initialization
    php_header = """<?php
/**
 * Ravenhill Coffee POS & Management System
 * Production Entry Point (PHP)
 */
require_once __DIR__ . '/api/utils/csrf.php';
$csrfToken = getCSRFToken();
?>
"""

    # Add CSRF token meta tag in head
    meta_tag = f"""  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
"""
    if '<meta name="csrf-token"' not in html:
        html = html.replace('<meta name="viewport" content="width=device-width, initial-scale=1.0">', '<meta name="viewport" content="width=device-width, initial-scale=1.0">\n' + meta_tag)

    php_content = php_header + html

    with open('index.php', 'w', encoding='utf-8') as f:
        f.write(php_content)
    print("Created index.php successfully!")

    # Remove index.html locally
    if os.path.exists('index.html'):
        os.remove('index.html')
        print("Removed local index.html!")

def remove_remote_html():
    HOST = 'mehedihasan.au'
    PORT = 2222
    USERNAME = 'mehedih3_cpro306_g1'
    PASSWORD = 'cpro306'
    
    try:
        transport = paramiko.Transport((HOST, PORT))
        transport.connect(username=USERNAME, password=PASSWORD)
        sftp = paramiko.SFTPClient.from_transport(transport)
        
        # Check and remove index.html on remote server
        for attr in sftp.listdir_attr('.'):
            if attr.filename.endswith('.html'):
                print(f"Removing remote HTML file: {attr.filename}")
                sftp.remove(attr.filename)
                
        sftp.close()
        transport.close()
        print("Cleaned remote HTML files via SFTP!")
    except Exception as e:
        print("Error cleaning remote HTML:", e)

if __name__ == '__main__':
    convert_html_to_php()
    remove_remote_html()
