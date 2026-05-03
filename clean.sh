#!/bin/bash

# 1. Meminta input path folder dari user
read -p "Masukkan path folder (contoh: /root/domains/data/generic_com): " target_dir

# 2. Mengecek apakah folder yang diinputkan benar-benar ada
if [ ! -d "$target_dir" ]; then
    echo "Error: Folder '$target_dir' tidak ditemukan!"
    exit 1
fi

# 3. Pindah ke folder target
cd "$target_dir" || exit
echo "Memulai proses di folder: $target_dir"
echo "------------------------------------------------"

# 4. Melakukan perulangan untuk semua file .txt
for file in *.txt; do
    # Lewati jika tidak menemukan file .txt sama sekali
    [ -e "$file" ] || { echo "Tidak ada file .txt ditemukan di folder ini."; break; }

    # Lewati file yang sudah berakhiran -clean.txt (agar tidak diproses ulang)
    if [[ "$file" == *"-clean.txt" ]]; then
        continue
    fi

    # Tentukan nama file output
    outfile="${file%.txt}-clean.txt"
    
    echo "Memproses: $file ..."

    # Proses pembersihan: Ambil root domain -> Hapus angka -> Hapus duplikat
    awk -F. 'NF>1 {print $(NF-1)"."$NF}' "$file" | grep -v '[0-9]' | sort -u > "$outfile"
    
    # 5. Keamanan: Cek apakah file output berhasil dibuat dan ada isinya (ukurannya lebih dari 0 bytes)
    if [ -s "$outfile" ]; then
        echo "-> Berhasil! Menghapus file asli: $file"
        rm "$file"  # Perintah untuk menghapus file asli
    else
        echo "-> Peringatan: File $outfile kosong atau gagal diproses. File asli TIDAK dihapus untuk keamanan."
    fi
done

echo "------------------------------------------------"
echo "Semua proses selesai!"
