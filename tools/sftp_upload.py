"""Nahraje složku na server přes SFTP (bez lftp/apt). Na serveru nic nemaže.
Nahrává jen soubory, které na serveru chybí nebo mají jinou velikost / jsou starší.
Použití: python3 tools/sftp_upload.py <lokalni_slozka>   (údaje z proměnných S, U, P, D)"""
import os, sys, stat, paramiko

src = sys.argv[1]
host, user, pw, dst = os.environ['S'], os.environ['U'], os.environ['P'], os.environ['D'].rstrip('/')
t = paramiko.Transport((host, 22))
t.banner_timeout = 60
t.connect(username=user, password=pw)
sf = paramiko.SFTPClient.from_transport(t)
sf.stat(dst)                      # cílová složka musí existovat
made = set()

def mkdirs(rd):
    if rd in made or rd == dst:
        return
    try:
        sf.stat(rd)
    except IOError:
        mkdirs(os.path.dirname(rd))
        sf.mkdir(rd)
        print('mkdir', rd)
    made.add(rd)

n = skip = 0
for root, _, files in os.walk(src):
    rel = os.path.relpath(root, src)
    rd = dst if rel == '.' else dst + '/' + rel.replace(os.sep, '/')
    mkdirs(rd)
    for fn in files:
        lp, rp = os.path.join(root, fn), rd + '/' + fn
        st = os.stat(lp)
        try:
            r = sf.stat(rp)
            if r.st_size == st.st_size and r.st_mtime >= int(st.st_mtime) and not fn.endswith(('.php', '.html', '.css', '.js', '.htaccess')):
                skip += 1
                continue
        except IOError:
            pass
        sf.put(lp, rp)
        n += 1
        print('put', rp)
sf.close(); t.close()
print(f'Nahráno {n} souborů, beze změny {skip}.')
