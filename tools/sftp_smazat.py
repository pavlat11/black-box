"""Smaže na serveru podsložku cílového webu (např. „test“) včetně obsahu.
Použití: python3 tools/sftp_smazat.py <podslozka>   (údaje z proměnných S, U, P, D)"""
import os, sys, stat, paramiko

name = sys.argv[1].strip('/')
if not name or '/' in name or name in ('.', '..', 'objednavky', 'css', 'js', 'img', 'fonts'):
    sys.exit('Tuto složku mazat nebudu: ' + name)
host, user, pw, dst = os.environ['S'], os.environ['U'], os.environ['P'], os.environ['D'].rstrip('/')
t = paramiko.Transport((host, 22)); t.banner_timeout = 60
t.connect(username=user, password=pw)
sf = paramiko.SFTPClient.from_transport(t)
target = dst + '/' + name

def rm(p):
    for e in sf.listdir_attr(p):
        q = p + '/' + e.filename
        if stat.S_ISDIR(e.st_mode):
            rm(q)
        else:
            sf.remove(q)
    sf.rmdir(p)

try:
    sf.stat(target)
except IOError:
    print('Složka', target, 'neexistuje – není co mazat.')
else:
    rm(target)
    print('Smazáno:', target)
sf.close(); t.close()
