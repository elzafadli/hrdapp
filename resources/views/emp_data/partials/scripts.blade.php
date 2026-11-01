<script>
function employeeFormManager() {
    return {
        init() {
            this.initTabComponents(this.activeTab);
            window.addEventListener('popstate', (e) => {
                try {
                    const currentTab = new URLSearchParams(window.location.search).get('tab') || 'data-karyawan';
                    if (this.activeTab !== currentTab) {
                        this.activeTab = currentTab;
                        this.initTabComponents(currentTab);
                    }
                } catch (err) {}
            });
        },
        activeTab: (function() {
            try {
                const p = new URLSearchParams(window.location.search).get('tab');
                if (p && ['data-karyawan', 'penugasan-payroll', 'karir', 'lama-bekerja', 'pendidikan', 'pengalaman-kerja', 'pelatihan-skill', 'bpjs'].includes(p)) {
                    return p;
                }
            } catch (e) {}
            return '{{ request('tab', 'data-karyawan') }}';
        })(),
        isDirty: false,
        isSubmitting: false,
        empExists: {{ $empData->exists ? 'true' : 'false' }},
        empId: {{ $empData->exists ? $empData->id : 'null' }},
        empName: {!! json_encode($empData->name ?? '') !!},
        confirmModalOpen: false,
        careerModalOpen: false,
        educationModalOpen: false,
        experienceModalOpen: false,
        trainingModalOpen: false,
        editingCareer: null,
        editingEducation: null,
        editingExperience: null,
        editingTraining: null,
        pendingTab: '',
        photoPreview: {!! json_encode($empData->photo_url ?? '') !!},
        careers: {!! json_encode($careerList->values()) !!},
        educations: {!! json_encode($educationList->values()) !!},
        experiences: {!! json_encode($experienceList->values()) !!},
        trainings: {!! json_encode($trainingList->values()) !!},
        tenureDisplay: {!! json_encode($empData->tenure_display_string ?? $empData->masa_kerja ?? '') !!},
        toast: { show: false, message: '', type: 'success', timer: null },
        fieldErrors: {},

        empUrl(subPath = '') {
            const base = '{{ url('/employee') }}';
            if (subPath === 'store') return base;
            if (!subPath) return `${base}/${this.empId}`;
            return `${base}/${this.empId}${subPath}`;
        },

        showToast(message, type = 'success') {
            if (this.toast.timer) clearTimeout(this.toast.timer);
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            this.toast.timer = setTimeout(() => {
                this.toast.show = false;
            }, 4500);
        },

        extractErrorMessage(data, fallback = 'Periksa kembali formulir Anda.') {
            if (!data) return fallback;
            if (data.errors && typeof data.errors === 'object') {
                const list = Object.values(data.errors).flat().filter(Boolean);
                if (list.length > 0) return list.join(' | ');
            }
            return data.message || fallback;
        },

        markDirty() {
            this.isDirty = true;
        },

        switchTab(tabName) {
            this.activeTab = tabName;
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabName);
                window.history.pushState({ tab: tabName }, '', url.toString());
            } catch (e) {
                console.error(e);
            }
            this.initTabComponents(tabName);
        },

        trySwitchTab(tabName) {
            if (!this.empExists && tabName !== 'data-karyawan') {
                this.showToast('Silakan simpan Data Karyawan terlebih dahulu di awal sebelum mengisi tab lainnya.', 'error');
                return;
            }

            if (this.activeTab === tabName) return;

            if (this.isDirty) {
                this.pendingTab = tabName;
                this.confirmModalOpen = true;
            } else {
                this.switchTab(tabName);
            }
        },

        confirmSwitchTab() {
            this.isDirty = false;
            this.confirmModalOpen = false;
            this.switchTab(this.pendingTab);
        },

        openCareerModal(career = null) {
            if (!this.empExists) {
                this.showToast('Silakan simpan Data Karyawan terlebih dahulu.', 'error');
                return;
            }
            this.editingCareer = career;
            this.careerModalOpen = true;
            this.$nextTick(() => {
                const elStatus = document.getElementById('modal_status_karyawan');
                const elPenempatan = document.getElementById('modal_penempatan_kerja');
                const elOrg = document.getElementById('modal_organisasi');
                const elPangkat = document.getElementById('modal_pangkat');
                const elJabatan = document.getElementById('modal_career_jabatan');
                const elNotes = document.getElementById('modal_career_notes');

                if (career) {
                    if (elStatus) elStatus.value = career.status_karyawan || 'PKWT';
                    if (elPenempatan) elPenempatan.value = career.penempatan_kerja || 'Baru Direkrut';
                    if (elOrg) elOrg.value = career.organisasi || 'IT';
                    if (elPangkat) elPangkat.value = career.pangkat || 'Staff';
                    if (elJabatan) elJabatan.value = career.jabatan || '';
                    if (elNotes) elNotes.value = career.notes || '';
                } else {
                    if (elStatus) elStatus.value = 'PKWT';
                    if (elPenempatan) elPenempatan.value = 'Baru Direkrut';
                    if (elOrg) elOrg.value = 'IT';
                    if (elPangkat) elPangkat.value = 'Staff';
                    if (elJabatan) elJabatan.value = '';
                    if (elNotes) elNotes.value = '';
                }

                if (typeof flatpickr !== 'undefined') {
                    const fp = flatpickr('#career_tanggal_efektif', {
                        disableMobile: true,
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'd/m/Y',
                        altInputClass: 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer',
                        locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default'
                    });
                    if (career && career.tanggal_efektif) {
                        fp.setDate(career.tanggal_efektif, true);
                    } else if (!career) {
                        fp.setDate(new Date(), true);
                    }
                }
            });
        },

        openEducationModal(edu = null) {
            if (!this.empExists) {
                this.showToast('Silakan simpan Data Karyawan terlebih dahulu.', 'error');
                return;
            }
            this.editingEducation = edu;
            this.educationModalOpen = true;
            this.$nextTick(() => {
                const elJenjang = document.getElementById('modal_edu_jenjang');
                const elInstitusi = document.getElementById('modal_edu_institusi');
                const elJurusan = document.getElementById('modal_edu_jurusan');
                const elNilai = document.getElementById('modal_edu_nilai');
                const elMasuk = document.getElementById('modal_edu_tahun_masuk');
                const elLulus = document.getElementById('modal_edu_tahun_lulus');
                const elNotes = document.getElementById('modal_edu_notes');

                if (edu) {
                    if (elJenjang) elJenjang.value = edu.jenjang || 'D4/S1';
                    if (elInstitusi) elInstitusi.value = edu.institusi || '';
                    if (elJurusan) elJurusan.value = edu.jurusan || '';
                    if (elNilai) elNilai.value = edu.nilai || '';
                    if (elMasuk) elMasuk.value = edu.tahun_masuk || '';
                    if (elLulus) elLulus.value = edu.tahun_lulus || '';
                    if (elNotes) elNotes.value = edu.notes || '';
                } else {
                    if (elJenjang) elJenjang.value = 'D4/S1';
                    if (elInstitusi) elInstitusi.value = '';
                    if (elJurusan) elJurusan.value = '';
                    if (elNilai) elNilai.value = '';
                    if (elMasuk) elMasuk.value = '';
                    if (elLulus) elLulus.value = '';
                    if (elNotes) elNotes.value = '';
                }
            });
        },

        openExperienceModal(exp = null) {
            if (!this.empExists) {
                this.showToast('Silakan simpan Data Karyawan terlebih dahulu.', 'error');
                return;
            }
            this.editingExperience = exp;
            this.experienceModalOpen = true;
            this.$nextTick(() => {
                const elPerusahaan = document.getElementById('modal_exp_perusahaan');
                const elJabatan = document.getElementById('modal_exp_jabatan');
                const elGaji = document.getElementById('modal_exp_gaji');
                const elIsCurrent = document.querySelector('input[name="is_current"]');
                const elDeskripsi = document.getElementById('modal_exp_deskripsi');
                const elAlasan = document.getElementById('modal_exp_alasan');

                if (exp) {
                    if (elPerusahaan) elPerusahaan.value = exp.perusahaan || '';
                    if (elJabatan) elJabatan.value = exp.jabatan || '';
                    if (elGaji) elGaji.value = exp.gaji_terakhir || '';
                    if (elIsCurrent) elIsCurrent.checked = !!exp.is_current;
                    if (elDeskripsi) elDeskripsi.value = exp.deskripsi_pekerjaan || '';
                    if (elAlasan) elAlasan.value = exp.alasan_berhenti || '';
                } else {
                    if (elPerusahaan) elPerusahaan.value = '';
                    if (elJabatan) elJabatan.value = '';
                    if (elGaji) elGaji.value = '';
                    if (elIsCurrent) elIsCurrent.checked = false;
                    if (elDeskripsi) elDeskripsi.value = '';
                    if (elAlasan) elAlasan.value = '';
                }

                if (typeof flatpickr !== 'undefined') {
                    const fpMulai = flatpickr('#exp_tanggal_mulai', {
                        disableMobile: true,
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'd/m/Y',
                        altInputClass: 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer',
                        locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default'
                    });
                    const fpSelesai = flatpickr('#exp_tanggal_selesai', {
                        disableMobile: true,
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'd/m/Y',
                        altInputClass: 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer',
                        locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default'
                    });

                    if (exp && exp.tanggal_mulai) {
                        fpMulai.setDate(exp.tanggal_mulai, true);
                    } else if (!exp) {
                        fpMulai.clear();
                    }

                    if (exp && exp.tanggal_selesai) {
                        fpSelesai.setDate(exp.tanggal_selesai, true);
                    } else if (!exp) {
                        fpSelesai.clear();
                    }
                }
            });
        },

        openTrainingModal(tr = null) {
            if (!this.empExists) {
                this.showToast('Silakan simpan Data Karyawan terlebih dahulu.', 'error');
                return;
            }
            this.editingTraining = tr;
            this.trainingModalOpen = true;
            this.$nextTick(() => {
                const elNama = document.getElementById('modal_tr_nama');
                const elPenyelenggara = document.getElementById('modal_tr_penyelenggara');
                const elTahun = document.getElementById('modal_tr_tahun');
                const elNomor = document.getElementById('modal_tr_nomor');
                const elMasa = document.getElementById('modal_tr_masa');
                const elDeskripsi = document.getElementById('modal_tr_deskripsi');

                if (tr) {
                    if (elNama) elNama.value = tr.nama_pelatihan || '';
                    if (elPenyelenggara) elPenyelenggara.value = tr.penyelenggara || '';
                    if (elTahun) elTahun.value = tr.tahun || '';
                    if (elNomor) elNomor.value = tr.nomor_sertifikat || '';
                    if (elMasa) elMasa.value = tr.masa_berlaku || '';
                    if (elDeskripsi) elDeskripsi.value = tr.deskripsi || '';
                } else {
                    if (elNama) elNama.value = '';
                    if (elPenyelenggara) elPenyelenggara.value = '';
                    if (elTahun) elTahun.value = '';
                    if (elNomor) elNomor.value = '';
                    if (elMasa) elMasa.value = '';
                    if (elDeskripsi) elDeskripsi.value = '';
                }
            });
        },

        initTabComponents(tab) {
            this.$nextTick(() => {
                if (typeof flatpickr !== 'undefined') {
                    if (tab === 'data-karyawan') {
                        flatpickr('#birth_date', {
                            disableMobile: true,
                            dateFormat: 'Y-m-d',
                            altInput: true,
                            altFormat: 'd M Y',
                            altInputClass: 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer',
                            maxDate: 'today',
                            locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default'
                        });
                    } else if (tab === 'lama-bekerja') {
                        flatpickr('#tenure_join_date', {
                            disableMobile: true,
                            dateFormat: 'Y-m-d',
                            altInput: true,
                            altFormat: 'd/m/Y',
                            altInputClass: 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer',
                            locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default'
                        });
                    }
                }
            });
        },

        async submitDataKaryawan(e) {
            e.preventDefault();
            this.fieldErrors = {};

            const form = e.target.tagName === 'FORM' ? e.target : (e.target.closest('form') || document.querySelector('form'));
            if (!form) return;

            const formData = new FormData(form);

            // Validasi cepat di sisi client untuk kolom wajib
            const nameVal = (formData.get('name') || '').trim();
            const jabatanVal = (formData.get('jabatan_id') || '').trim();
            const ptkpVal = (formData.get('status_ptkp') || '').trim();

            if (!nameVal) {
                this.fieldErrors = { name: ['Nama Lengkap (Sesuai KTP) wajib diisi.'] };
                this.showToast('Nama Lengkap (Sesuai KTP) wajib diisi.', 'error');
                const nameEl = form.querySelector('[name="name"]');
                if (nameEl) nameEl.focus();
                return;
            }

            if (!jabatanVal) {
                this.fieldErrors = { jabatan_id: ['Jabatan karyawan wajib dipilih.'] };
                this.showToast('Jabatan karyawan wajib dipilih.', 'error');
                return;
            }
            if (!ptkpVal) {
                this.fieldErrors = { status_ptkp: ['Status PTKP wajib dipilih.'] };
                this.showToast('Status PTKP wajib dipilih.', 'error');
                return;
            }

            this.isSubmitting = true;
            const targetUrl = this.empExists ? this.empUrl() : '{{ route('employee.store') }}';

            if (this.empExists) {
                formData.append('_method', 'PUT');
            }

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.isDirty = false;
                    this.showToast(data.message || 'Data Karyawan berhasil disimpan.', 'success');
                    if (data.is_new) {
                        this.empExists = true;
                        this.empId = data.employee_id;
                        this.empName = data.employee_name;
                        if (data.photo_url) this.photoPreview = data.photo_url;
                        try {
                            const newUrl = new URL(data.edit_url, window.location.origin);
                            newUrl.searchParams.set('tab', this.activeTab);
                            window.history.replaceState({ tab: this.activeTab }, '', newUrl.toString());
                        } catch (e) {
                            window.history.replaceState({}, '', data.edit_url);
                        }
                    } else {
                        this.empName = data.employee_name;
                        if (data.photo_url) this.photoPreview = data.photo_url;
                    }
                } else if (response.status === 422) {
                    this.fieldErrors = data.errors || {};
                    this.showToast(this.extractErrorMessage(data, 'Periksa kembali formulir Anda.'), 'error');
                } else {
                    this.showToast(data.message || 'Terjadi kesalahan sistem.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async submitCareer(e) {
            e.preventDefault();
            if (!this.empId) return;
            this.isSubmitting = true;

            const form = e.target;
            const formData = new FormData(form);
            const isEdit = !!this.editingCareer;
            const targetUrl = isEdit ? this.empUrl('/career/' + this.editingCareer.id) : this.empUrl('/career');
            if (isEdit) {
                formData.append('_method', 'PUT');
            }

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.isDirty = false;
                    this.careerModalOpen = false;
                    this.showToast(data.message || (isEdit ? 'Riwayat Karir berhasil diperbarui.' : 'Riwayat Karir berhasil ditambahkan.'), 'success');
                    if (data.career) {
                        if (isEdit) {
                            const idx = this.careers.findIndex(c => c.id === this.editingCareer.id);
                            if (idx !== -1) {
                                this.careers.splice(idx, 1, data.career);
                            }
                        } else {
                            this.careers.forEach(c => c.is_current = false);
                            this.careers.unshift(data.career);
                        }
                    }
                    this.editingCareer = null;
                    form.reset();
                } else if (response.status === 422) {
                    this.showToast(this.extractErrorMessage(data, 'Periksa isian karir.'), 'error');
                } else {
                    this.showToast(data.message || 'Gagal menyimpan karir.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async deleteCareer(careerId) {
            if (!confirm('Apakah Anda yakin ingin menghapus riwayat karir ini?')) return;
            try {
                const response = await fetch(this.empUrl('/career/' + careerId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.careers = this.careers.filter(c => c.id !== careerId);
                    this.showToast(data.message || 'Riwayat karir berhasil dihapus.', 'success');
                } else {
                    this.showToast(data.message || 'Gagal menghapus riwayat karir.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            }
        },

        async submitTenure(e) {
            e.preventDefault();
            if (!this.empId) return;
            this.isSubmitting = true;

            const form = e.target;
            const formData = new FormData(form);
            const targetUrl = this.empUrl('/tenure');

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.isDirty = false;
                    this.showToast(data.message || 'Data Lama Bekerja berhasil diperbarui.', 'success');
                    if (data.tenure_display_string) {
                        this.tenureDisplay = data.tenure_display_string;
                    }
                } else if (response.status === 422) {
                    this.showToast(this.extractErrorMessage(data, 'Periksa tanggal mulai kerja.'), 'error');
                } else {
                    this.showToast(data.message || 'Gagal menyimpan masa kerja.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async submitEducation(e) {
            e.preventDefault();
            if (!this.empId) return;
            this.isSubmitting = true;

            const form = e.target;
            const formData = new FormData(form);
            const isEdit = !!this.editingEducation;
            const targetUrl = isEdit ? this.empUrl('/education/' + this.editingEducation.id) : this.empUrl('/education');
            if (isEdit) {
                formData.append('_method', 'PUT');
            }

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.isDirty = false;
                    this.educationModalOpen = false;
                    this.showToast(data.message || (isEdit ? 'Riwayat Pendidikan berhasil diperbarui.' : 'Riwayat Pendidikan berhasil ditambahkan.'), 'success');
                    if (data.education) {
                        if (isEdit) {
                            const idx = this.educations.findIndex(e => e.id === this.editingEducation.id);
                            if (idx !== -1) {
                                this.educations.splice(idx, 1, data.education);
                            }
                        } else {
                            this.educations.unshift(data.education);
                        }
                    }
                    this.editingEducation = null;
                    form.reset();
                } else if (response.status === 422) {
                    this.showToast(this.extractErrorMessage(data, 'Periksa isian pendidikan.'), 'error');
                } else {
                    this.showToast(data.message || 'Gagal menyimpan pendidikan.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async deleteEducation(eduId) {
            if (!confirm('Apakah Anda yakin ingin menghapus data pendidikan ini?')) return;
            try {
                const response = await fetch(this.empUrl('/education/' + eduId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.educations = this.educations.filter(e => e.id !== eduId);
                    this.showToast(data.message || 'Riwayat pendidikan berhasil dihapus.', 'success');
                } else {
                    this.showToast(data.message || 'Gagal menghapus riwayat pendidikan.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            }
        },

        async submitExperience(e) {
            e.preventDefault();
            if (!this.empId) return;
            this.isSubmitting = true;

            const form = e.target;
            const formData = new FormData(form);
            const isEdit = !!this.editingExperience;
            const targetUrl = isEdit ? this.empUrl('/experience/' + this.editingExperience.id) : this.empUrl('/experience');
            if (isEdit) {
                formData.append('_method', 'PUT');
            }

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.isDirty = false;
                    this.experienceModalOpen = false;
                    this.showToast(data.message || (isEdit ? 'Riwayat Pengalaman Kerja berhasil diperbarui.' : 'Riwayat Pengalaman Kerja berhasil ditambahkan.'), 'success');
                    if (data.experience) {
                        if (isEdit) {
                            const idx = this.experiences.findIndex(e => e.id === this.editingExperience.id);
                            if (idx !== -1) {
                                this.experiences.splice(idx, 1, data.experience);
                            }
                        } else {
                            this.experiences.unshift(data.experience);
                        }
                    }
                    this.editingExperience = null;
                    form.reset();
                } else if (response.status === 422) {
                    this.showToast(this.extractErrorMessage(data, 'Periksa isian pengalaman kerja.'), 'error');
                } else {
                    this.showToast(data.message || 'Gagal menyimpan pengalaman kerja.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async deleteExperience(expId) {
            if (!confirm('Apakah Anda yakin ingin menghapus riwayat pengalaman kerja ini?')) return;
            try {
                const response = await fetch(this.empUrl('/experience/' + expId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.experiences = this.experiences.filter(e => e.id !== expId);
                    this.showToast(data.message || 'Riwayat pengalaman kerja berhasil dihapus.', 'success');
                } else {
                    this.showToast(data.message || 'Gagal menghapus pengalaman kerja.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            }
        },

        async submitTraining(e) {
            e.preventDefault();
            if (!this.empId) return;
            this.isSubmitting = true;

            const form = e.target;
            const formData = new FormData(form);
            const isEdit = !!this.editingTraining;
            const targetUrl = isEdit ? this.empUrl('/training/' + this.editingTraining.id) : this.empUrl('/training');
            if (isEdit) {
                formData.append('_method', 'PUT');
            }

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.isDirty = false;
                    this.trainingModalOpen = false;
                    this.showToast(data.message || (isEdit ? 'Data Pelatihan & Keahlian berhasil diperbarui.' : 'Data Pelatihan & Keahlian berhasil ditambahkan.'), 'success');
                    if (data.training) {
                        if (isEdit) {
                            const idx = this.trainings.findIndex(t => t.id === this.editingTraining.id);
                            if (idx !== -1) {
                                this.trainings.splice(idx, 1, data.training);
                            }
                        } else {
                            this.trainings.unshift(data.training);
                        }
                    }
                    this.editingTraining = null;
                    form.reset();
                } else if (response.status === 422) {
                    this.showToast(this.extractErrorMessage(data, 'Periksa isian pelatihan.'), 'error');
                } else {
                    this.showToast(data.message || 'Gagal menyimpan pelatihan.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async deleteTraining(trainingId) {
            if (!confirm('Apakah Anda yakin ingin menghapus data pelatihan & sertifikasi ini?')) return;
            try {
                const response = await fetch(this.empUrl('/training/' + trainingId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.trainings = this.trainings.filter(t => t.id !== trainingId);
                    this.showToast(data.message || 'Data pelatihan & sertifikasi berhasil dihapus.', 'success');
                } else {
                    this.showToast(data.message || 'Gagal menghapus data pelatihan.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            }
        },

        async submitAssignment(e) {
            e.preventDefault();
            if (!this.empId) return;
            this.isSubmitting = true;
            this.fieldErrors = {};

            const form = e.target;
            const formData = new FormData(form);
            const targetUrl = this.empUrl('/assignment');

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.isDirty = false;
                    this.showToast(data.message || 'Penugasan & Status Payroll berhasil diperbarui.', 'success');
                } else if (response.status === 422) {
                    if (data.errors) {
                        this.fieldErrors = data.errors;
                    }
                    this.showToast(this.extractErrorMessage(data, 'Periksa data penugasan & payroll.'), 'error');
                } else {
                    this.showToast(data.message || 'Gagal menyimpan penugasan.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        async submitBpjs(e) {
            e.preventDefault();
            if (!this.empId) return;
            this.isSubmitting = true;

            const form = e.target;
            const formData = new FormData(form);
            const targetUrl = this.empUrl('/bpjs');

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.isDirty = false;
                    this.showToast(data.message || 'Data BPJS berhasil diperbarui.', 'success');
                } else if (response.status === 422) {
                    this.showToast(this.extractErrorMessage(data, 'Periksa isian BPJS.'), 'error');
                } else {
                    this.showToast(data.message || 'Gagal menyimpan data BPJS.', 'error');
                }
            } catch (err) {
                console.error(err);
                this.showToast('Gagal menghubungi server.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>

