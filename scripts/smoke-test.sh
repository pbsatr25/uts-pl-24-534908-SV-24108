#!/bin/sh
# ===========================================================================
# Smoke test stack Docker Compose: memastikan alur Browser -> Nginx -> Laravel
# (FrankenPHP) -> MySQL benar-benar bekerja, termasuk CRUD tugas.
#
# Pemakaian:  sh scripts/smoke-test.sh [alamat-frontend]
# Contoh:     sh scripts/smoke-test.sh http://localhost:8080
# ===========================================================================
set -eu

BASE_URL="${1:-http://localhost:8080}"
API="${BASE_URL}/api"
EMAIL="${SMOKE_EMAIL:-admin@kepl.test}"
PASSWORD="${SMOKE_PASSWORD:-password}"

BODY=$(mktemp)
trap 'rm -f "${BODY}"' EXIT

gagal() {
    echo "GAGAL: $1" >&2
    exit 1
}

# panggil METODE /alamat [opsi curl...]
# Body respons disimpan di ${BODY}; yang dicetak hanya kode status HTTP.
panggil() {
    metode="$1"
    alamat="$2"
    shift 2
    curl -sS -o "${BODY}" -w '%{http_code}' -X "${metode}" "${API}${alamat}" \
        -H 'Accept: application/json' -H 'Content-Type: application/json' "$@"
}

# cek KODE_DIDAPAT KODE_DIHARAPKAN keterangan
cek() {
    if [ "$1" != "$2" ]; then
        cat "${BODY}" >&2
        echo >&2
        gagal "$3 membalas $1, seharusnya $2"
    fi
    echo "      OK ($1)"
}

echo "[1/8] Halaman frontend dapat dibuka"
curl -fsS -o /dev/null "${BASE_URL}/" || gagal "frontend tidak merespons"
echo "      OK"

echo "[2/8] Tanpa token ditolak"
cek "$(panggil GET /tugas)" 401 "GET /tugas tanpa token"

echo "[3/8] Login lewat /api (Nginx meneruskan ke backend)"
cek "$(panggil POST /login -d "{\"email\":\"${EMAIL}\",\"password\":\"${PASSWORD}\"}")" 200 "POST /login"
token=$(sed -n 's/.*"token":"\([^"]*\)".*/\1/p' "${BODY}")
[ -n "${token}" ] || gagal "login tidak mengembalikan token"
AUTH="Authorization: Bearer ${token}"

echo "[4/8] Daftar tugas dari MySQL"
cek "$(panggil GET /tugas -H "${AUTH}")" 200 "GET /tugas"
grep -q '"data":\[' "${BODY}" || gagal "format daftar tugas tidak sesuai"

echo "[5/8] Tambah tugas"
cek "$(panggil POST /tugas -H "${AUTH}" -d '{"judul":"Smoke test","prioritas":"rendah","status":"belum"}')" 201 "POST /tugas"
id=$(sed -n 's/.*"data":{"id":\([0-9]*\).*/\1/p' "${BODY}")
[ -n "${id}" ] || gagal "id tugas baru tidak ditemukan"

echo "[6/8] Validasi gagal"
cek "$(panggil POST /tugas -H "${AUTH}" -d '{"judul":""}')" 422 "POST /tugas dengan data kosong"

echo "[7/8] Ubah, hapus, lalu pastikan tugas ${id} sudah tidak ada"
cek "$(panggil PATCH "/tugas/${id}" -H "${AUTH}" -d '{"status":"selesai"}')" 200 "PATCH /tugas/${id}"
cek "$(panggil DELETE "/tugas/${id}" -H "${AUTH}")" 200 "DELETE /tugas/${id}"
cek "$(panggil GET "/tugas/${id}" -H "${AUTH}")" 404 "GET /tugas/${id} setelah dihapus"

echo "[8/8] Logout mencabut token"
cek "$(panggil POST /logout -H "${AUTH}")" 200 "POST /logout"
cek "$(panggil GET /me -H "${AUTH}")" 401 "GET /me dengan token yang sudah dicabut"

echo "Smoke test lolos."
