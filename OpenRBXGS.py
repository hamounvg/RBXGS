import subprocess
import os
import sys
import time

exe_path = r"C:\Users\nibas\Downloads\GCC\GCCService\GCCService\RBXGSConhost.exe"

def validate_exe(path):
    if not os.path.isfile(path):
        return False
    return True

def launch_exe(path):
    try:
        return subprocess.Popen([path])
    except Exception:
        sys.exit(1)

if __name__ == "__main__":
    if not validate_exe(exe_path):
        sys.exit(1)

    while True:
        proc = launch_exe(exe_path)
        input()
        try:
            proc.terminate()
            time.sleep(0.2)
            if proc.poll() is None:
                proc.kill()
        except Exception:
            pass
