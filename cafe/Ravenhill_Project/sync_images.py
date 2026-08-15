import os
import shutil
import paramiko

def copy_images_locally():
    src_dir = 'Brand Resources'
    dst_dir = '.'
    for f in os.listdir(src_dir):
        if f.endswith('.png') or f.endswith('.jpg') or f.endswith('.jpeg') or f.endswith('.webp'):
            src = os.path.join(src_dir, f)
            dst = os.path.join(dst_dir, f)
            shutil.copy2(src, dst)
            print(f"Copied {f} to root directory.")

def update_get_item_image():
    with open('app.js', 'r', encoding='utf-8') as f:
        js = f.read()

    new_func = """window.getItemImage = function(item) {
  if (!item) return 'flat_white_coffee.png';
  
  if (item.image && typeof item.image === 'string' && item.image.trim() !== '') {
    let img = item.image.trim();
    // If it includes path or full URL, return it
    if (img.startsWith('http') || img.startsWith('./') || img.startsWith('/')) return img;
    // Strip Brand Resources prefix if present
    img = img.replace(/^Brand%20Resources\\//, '').replace(/^Brand Resources\\//, '');
    return img;
  }
  
  const name = (item.name || item.product_name || '').toLowerCase();

  if (name.includes('flat white') || name.includes('latte')) return 'flat_white_coffee.png';
  if (name.includes('cappuccino') || name.includes('mocha') || name.includes('babycino') || name.includes('chocolate')) return 'cappuccino_coffee.png';
  if (name.includes('espresso') || name.includes('short black') || name.includes('macchiato')) return 'double_espresso_short_black.png';
  if (name.includes('long black') || name.includes('ristretto') || name.includes('americano')) return 'long_black_coffee.png';
  if (name.includes('piccolo')) return 'piccolo_latte.png';
  if (name.includes('batch brew') || name.includes('filter')) return 'batch_brew_filter.png';
  if (name.includes('pour-over') || name.includes('v60')) return 'v60_pourover_coffee.png';
  if (name.includes('cold brew') || name.includes('water') || name.includes('drink') || name.includes('juice')) return 'cold_brew_coffee.png';
  if (name.includes('iced') || name.includes('shake') || name.includes('smoothie')) return 'iced_oat_milk_latte.png';
  if (name.includes('chai') || name.includes('tea') || name.includes('matcha') || name.includes('turmeric')) return 'prana_sticky_chai_latte.png';
  if (name.includes('croissant') || name.includes('toast') || name.includes('wrap') || name.includes('roll') || name.includes('sandwich') || name.includes('muffin') || name.includes('bread') || name.includes('scone') || name.includes('salad') || name.includes('chip') || name.includes('burger') || name.includes('egg') || name.includes('avocado')) return 'butter_croissant.png';
  if (name.includes('bean') || name.includes('reserve')) return 'roasted_coffee_beans.png';

  return 'flat_white_coffee.png';
};"""

    import re
    pattern = re.compile(r"window\.getItemImage\s*=\s*function\(item\)\s*\{.*?\n\};", re.DOTALL)
    js = pattern.sub(new_func, js)

    with open('app.js', 'w', encoding='utf-8') as f:
        f.write(js)
    print("Updated getItemImage in app.js successfully!")

def upload_images_to_remote():
    HOST = 'mehedihasan.au'
    PORT = 2222
    USERNAME = 'mehedih3_cpro306_g1'
    PASSWORD = 'cpro306'
    
    transport = paramiko.Transport((HOST, PORT))
    transport.connect(username=USERNAME, password=PASSWORD)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    src_dir = 'Brand Resources'
    for f in os.listdir(src_dir):
        if f.endswith('.png') or f.endswith('.jpg'):
            local_path = os.path.join(src_dir, f)
            remote_path = f  # upload directly to root
            print(f"Uploading image to remote root: {f}")
            sftp.put(local_path, remote_path)
            
    # Also upload updated app.js
    sftp.put('app.js', 'app.js')
    print("Uploaded updated app.js to remote server!")

    sftp.close()
    transport.close()
    print("Finished uploading images to remote server!")

if __name__ == '__main__':
    copy_images_locally()
    update_get_item_image()
    upload_images_to_remote()
