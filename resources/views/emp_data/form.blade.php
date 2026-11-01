@php
    $careerList = ($empData->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_careers')) ? $empData->careers : collect();
    $educationList = ($empData->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_educations')) ? $empData->educations : collect();
    $experienceList = ($empData->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_experiences')) ? $empData->experiences : collect();
    $trainingList = ($empData->exists && \Illuminate\Support\Facades\Schema::hasTable('emp_trainings')) ? $empData->trainings : collect();
@endphp

<x-app-layout>
    <div class="py-6 bg-slate-50 min-h-screen" x-data="{
        activeTab: (function() {
            try {
                const p = new URLSearchParams(window.location.search).get('tab');
                if (p && ['data-karyawan', 'karir', 'lama-bekerja', 'pendidikan', 'pengalaman-kerja', 'pelatihan-skill', 'penugasan-payroll', 'bpjs'].includes(p)) {
                    return p;
                }
            } catch (e) {}
            return '{{ request('tab', 'data-karyawan') }}';
        })(),
        isDirty: false,
        isSubmitting: false,
        empExists: {{ $empData->exists ? 'true' : 'false' }},
        empId: {{ $empData->exists ? $empData->id : 'null' }},
        empName: '{{ addslashes($empData->name ?? '') }}',
        confirmModalOpen: false,
        careerModalOpen: false,
        educationModalOpen: false,
        experienceModalOpen: false,
        trainingModalOpen: false,
        pendingTab: '',
        photoPreview: '{{ $empData->photo_url ?? '' }}',
        careers: {{ json_encode($careerList->values()) }},
        educations: {{ json_encode($educationList->values()) }},
        experiences: {{ json_encode($experienceList->values()) }},
        trainings: {{ json_encode($trainingList->values()) }},
        tenureDisplay: '{{ addslashes($empData->tenure_display_string ?? $empData->masa_kerja ?? '') }}',
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

        openCareerModal() {
            if (!this.empExists) {
                this.showToast('Silakan simpan Data Karyawan terlebih dahulu.', 'error');
                return;
            }
            this.careerModalOpen = true;
            this.$nextTick(() => {
                if (typeof flatpickr !== 'undefined') {
                    flatpickr('#career_tanggal_efektif', {
                        disableMobile: true,
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'd/m/Y',
                        altInputClass: 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer',
                        locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default'
                    });
                }
            });
        },

        openEducationModal() {
            if (!this.empExists) {
                this.showToast('Silakan simpan Data Karyawan terlebih dahulu.', 'error');
                return;
            }
            this.educationModalOpen = true;
        },

        openExperienceModal() {
            if (!this.empExists) {
                this.showToast('Silakan simpan Data Karyawan terlebih dahulu.', 'error');
                return;
            }
            this.experienceModalOpen = true;
            this.$nextTick(() => {
                if (typeof flatpickr !== 'undefined') {
                    flatpickr('#exp_tanggal_mulai', {
                        disableMobile: true,
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'd/m/Y',
                        altInputClass: 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer',
                        locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default'
                    });
                    flatpickr('#exp_tanggal_selesai', {
                        disableMobile: true,
                        dateFormat: 'Y-m-d',
                        altInput: true,
                        altFormat: 'd/m/Y',
                        altInputClass: 'border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer',
                        locale: (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.id) ? flatpickr.l10ns.id : 'default'
                    });
                }
            });
        },

        openTrainingModal() {
            if (!this.empExists) {
                this.showToast('Silakan simpan Data Karyawan terlebih dahulu.', 'error');
                return;
            }
            this.trainingModalOpen = true;
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

            if (!this.empExists) {
                if (!jabatanVal) {
                    this.fieldErrors = { jabatan_id: ['Jabatan awal karyawan wajib dipilih.'] };
                    this.showToast('Jabatan awal karyawan wajib dipilih.', 'error');
                    return;
                }
                if (!ptkpVal) {
                    this.fieldErrors = { status_ptkp: ['Status PTKP awal wajib dipilih.'] };
                    this.showToast('Status PTKP awal wajib dipilih.', 'error');
                    return;
                }
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
            const targetUrl = this.empUrl('/career');

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
                    this.showToast(data.message || 'Riwayat Karir berhasil ditambahkan.', 'success');
                    if (data.career) {
                        this.careers.forEach(c => c.is_current = false);
                        this.careers.unshift(data.career);
                    }
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
            const targetUrl = this.empUrl('/education');

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
                    this.showToast(data.message || 'Riwayat Pendidikan berhasil ditambahkan.', 'success');
                    if (data.education) {
                        this.educations.unshift(data.education);
                    }
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
            const targetUrl = this.empUrl('/experience');

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
                    this.showToast(data.message || 'Riwayat Pengalaman Kerja berhasil ditambahkan.', 'success');
                    if (data.experience) {
                        this.experiences.unshift(data.experience);
                    }
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
            const targetUrl = this.empUrl('/training');

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
                    this.showToast(data.message || 'Data Pelatihan & Keahlian berhasil ditambahkan.', 'success');
                    if (data.training) {
                        this.trainings.unshift(data.training);
                    }
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
    }" x-init="
        initTabComponents(activeTab);
        window.addEventListener('popstate', (e) => {
            try {
                const currentTab = new URLSearchParams(window.location.search).get('tab') || 'data-karyawan';
                if (activeTab !== currentTab) {
                    activeTab = currentTab;
                    initTabComponents(currentTab);
                }
            } catch (err) {}
        });
    ">

        {{-- FLOATING CLIENT-SIDE TOAST NOTIFICATION --}}
        <div x-show="toast.show" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
            class="fixed top-5 right-5 z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl text-xs font-semibold border backdrop-blur-md max-w-md"
            :class="toast.type === 'success' ? 'bg-emerald-600/95 text-white border-emerald-500 shadow-emerald-500/25' : 'bg-rose-600/95 text-white border-rose-500 shadow-rose-500/25'"
            style="display: none;">
            <i class="fa-solid text-base shrink-0"
                :class="toast.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
            <span class="leading-relaxed" x-text="toast.message"></span>
            <button type="button" @click="toast.show = false"
                class="ml-2 p-1 text-white/80 hover:text-white rounded-lg transition shrink-0">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Top Header Nav --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <a href="{{ route('employee.index') }}"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-white border border-gray-200 text-gray-600 hover:text-blue-600 hover:border-blue-300 shadow-xs transition"
                        title="Kembali ke Data Karyawan">
                        <i class="fa-solid fa-arrow-left text-sm"></i>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 tracking-tight"
                            x-text="empExists ? ('Kelola Data Karyawan: ' + empName) : 'Tambah Karyawan Baru'">
                        </h1>
                        <p class="text-xs text-gray-500 mt-0.5"
                            x-text="empExists ? 'Kelola identitas, riwayat karir, lama bekerja, riwayat pendidikan, dan payroll' : 'Lengkapi data identitas karyawan di awal sebelum menambahkan karir, pendidikan, dan payroll'">
                        </p>
                    </div>
                </div>

                <div x-show="empExists" class="flex items-center gap-2">
                    <a :href="empUrl()" href="{{ $empData->exists ? route('employee.show', $empData->id) : '#' }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-xs transition">
                        <i class="fa-solid fa-eye text-blue-500"></i>
                        Lihat Profil
                    </a>
                </div>
            </div>

            {{-- Alert Notifikasi Sukses Server (Jika Redirect Tradisional) --}}
            @if(session('success'))
                <div
                    class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-3 shadow-xs">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- Notice Awal jika Karyawan Baru (Auto-Hide Saat Disimpan via AJAX) --}}
            <div x-show="!empExists" x-cloak
                class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start justify-between gap-3 shadow-xs">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-info text-amber-500 text-base mt-0.5"></i>
                    <div>
                        <span class="font-bold block">Tahap Awal: Simpan Data Karyawan Terlebih Dahulu</span>
                        <span>Tab Karir, Lama Bekerja, Pendidikan, Penugasan & Payroll, dan Data BPJS saat ini terkunci.
                            Setelah Data Karyawan pertama kali disimpan, Anda dapat langsung mengelola setiap tab secara
                            mandiri tanpa refresh.</span>
                    </div>
                </div>
                <div class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-amber-200 text-slate-700 font-semibold whitespace-nowrap text-xs shadow-2xs">
                    <span class="text-red-500 font-bold text-sm">*</span>
                    <span>Wajib diisi</span>
                </div>
            </div>

            {{-- TAB NAVIGATION BAR (6 TAB MANDIRI DENGAN WARNING DIRTY) --}}
            <div
                class="bg-white rounded-2xl border border-gray-200 p-1.5 shadow-xs mb-6 flex flex-wrap items-center gap-1">

                {{-- TAB 1: DATA KARYAWAN --}}
                <button type="button" @click="trySwitchTab('data-karyawan')"
                    class="flex-1 min-w-[130px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition"
                    :class="activeTab === 'data-karyawan' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'">
                    <i class="fa-regular fa-user"></i>
                    <span>Data Karyawan</span>
                </button>

                {{-- TAB 2: KARIR --}}
                <button type="button" @click="trySwitchTab('karir')"
                    class="flex-1 min-w-[110px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition relative"
                    :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'karir' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
                    <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-briefcase'"></i>
                    <span>Karir</span>
                    <span x-show="careers.length > 0" class="ml-0.5 px-1.5 py-0.2 text-[10px] rounded-full font-mono"
                        :class="activeTab === 'karir' ? 'bg-white text-blue-700' : 'bg-blue-100 text-blue-700'"
                        x-text="careers.length">
                    </span>
                </button>

                {{-- TAB 3: LAMA BEKERJA --}}
                <button type="button" @click="trySwitchTab('lama-bekerja')"
                    class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition"
                    :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'lama-bekerja' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
                    <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-regular fa-calendar-check'"></i>
                    <span>Lama Bekerja</span>
                </button>

                {{-- TAB 4: PENDIDIKAN (BISA MULTI) --}}
                <button type="button" @click="trySwitchTab('pendidikan')"
                    class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition relative"
                    :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'pendidikan' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
                    <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-graduation-cap'"></i>
                    <span>Pendidikan</span>
                    <span x-show="educations.length > 0" class="ml-0.5 px-1.5 py-0.2 text-[10px] rounded-full font-mono"
                        :class="activeTab === 'pendidikan' ? 'bg-white text-blue-700' : 'bg-emerald-100 text-emerald-700'"
                        x-text="educations.length">
                    </span>
                </button>

                {{-- TAB 5: PENGALAMAN KERJA (BISA MULTI) --}}
                <button type="button" @click="trySwitchTab('pengalaman-kerja')"
                    class="flex-1 min-w-[145px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition relative"
                    :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'pengalaman-kerja' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
                    <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-building-user'"></i>
                    <span>Pengalaman Kerja</span>
                    <span x-show="experiences.length > 0"
                        class="ml-0.5 px-1.5 py-0.2 text-[10px] rounded-full font-mono"
                        :class="activeTab === 'pengalaman-kerja' ? 'bg-white text-blue-700' : 'bg-indigo-100 text-indigo-700'"
                        x-text="experiences.length">
                    </span>
                </button>

                {{-- TAB 6: TRAINING & SKILL (BISA MULTI) --}}
                <button type="button" @click="trySwitchTab('pelatihan-skill')"
                    class="flex-1 min-w-[140px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition relative"
                    :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'pelatihan-skill' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
                    <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-award'"></i>
                    <span>Pelatihan & Skill</span>
                    <span x-show="trainings.length > 0" class="ml-0.5 px-1.5 py-0.2 text-[10px] rounded-full font-mono"
                        :class="activeTab === 'pelatihan-skill' ? 'bg-white text-blue-700' : 'bg-amber-100 text-amber-700'"
                        x-text="trainings.length">
                    </span>
                </button>

                {{-- TAB 7: PENUGASAN & STATUS PAYROLL --}}
                <button type="button" @click="trySwitchTab('penugasan-payroll')"
                    class="flex-1 min-w-[140px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition"
                    :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'penugasan-payroll' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
                    <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-sliders'"></i>
                    <span>Penugasan & Payroll</span>
                </button>

                {{-- TAB 6: DATA BPJS --}}
                <button type="button" @click="trySwitchTab('bpjs')"
                    class="flex-1 min-w-[120px] flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-bold transition"
                    :class="!empExists ? 'text-gray-300 bg-gray-50/50 cursor-not-allowed opacity-60' : (activeTab === 'bpjs' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100')">
                    <i class="fa-solid" :class="!empExists ? 'fa-lock' : 'fa-shield-heart'"></i>
                    <span>Data BPJS</span>
                </button>

            </div>

            {{-- ========================================================================= --}}
            {{-- TAB CONTENT 1: DATA KARYAWAN (SAVE BERDIRI SENDIRI VIA AJAX) --}}
            {{-- ========================================================================= --}}
            <div x-show="activeTab === 'data-karyawan'" class="space-y-6">
                <form novalidate method="POST" :action="empExists ? empUrl() : '{{ route('employee.store') }}'"
                    enctype="multipart/form-data" @input="markDirty()" @change="markDirty()"
                    @submit.prevent="submitDataKaryawan($event)">
                    @csrf

                    <div class="space-y-6">

                        {{-- CARD 1A: FOTO PROFIL & IDENTITAS UTAMA --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                            <div
                                class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-regular fa-image"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-sm font-bold text-gray-900">Foto Profil & Akun Karyawan</h2>
                                        <p class="text-xs text-gray-500">Foto ini hanya bisa diubah oleh tim HRD</p>
                                    </div>
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                                    {{-- Avatar Preview --}}
                                    <div class="flex flex-col items-center gap-2">
                                        <div
                                            class="relative w-28 h-28 rounded-full border-4 border-white shadow-md bg-slate-100 flex items-center justify-center overflow-hidden">
                                            <template x-if="photoPreview">
                                                <img :src="photoPreview" alt="Foto Profil"
                                                    class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!photoPreview">
                                                <div
                                                    class="w-full h-full flex flex-col items-center justify-center bg-blue-50 text-blue-600">
                                                    <i class="fa-regular fa-user text-3xl"></i>
                                                </div>
                                            </template>
                                        </div>
                                        <span class="text-[11px] text-gray-500 font-medium text-center max-w-[160px]">
                                            Maksimal 2 MB (JPG, PNG, WebP)
                                        </span>
                                    </div>

                                    {{-- Upload & Info Input --}}
                                    <div class="flex-1 w-full space-y-4">
                                        <div>
                                            <x-input-label for="photo" :value="__('Unggah Foto Profil')" />
                                            <input type="file" id="photo" name="photo" accept="image/*" @change="
                                                    const file = $event.target.files[0];
                                                    if (file) {
                                                        const reader = new FileReader();
                                                        reader.onload = (e) => { photoPreview = e.target.result; };
                                                        reader.readAsDataURL(file);
                                                    }
                                                    markDirty();
                                                "
                                                class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-200 rounded-xl bg-white p-1" />
                                            <x-input-error class="mt-1.5" :messages="$errors->get('photo')" />
                                            <p x-show="fieldErrors['photo']"
                                                x-text="fieldErrors['photo'] ? fieldErrors['photo'][0] : ''"
                                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                            <p
                                                class="text-[11px] text-amber-600 mt-1 flex items-center gap-1 font-medium">
                                                <i class="fa-solid fa-circle-info"></i> Foto ini hanya bisa diubah oleh
                                                tim HRD
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <x-input-label for="nik_karyawan" :value="__('ID Karyawan (Nomor Induk Karyawan)')" />
                                                <x-text-input id="nik_karyawan" name="nik_karyawan" type="text"
                                                    class="mt-1 block w-full font-mono" :value="old('nik_karyawan', $empData->nik_karyawan ?? $empData->id)"
                                                    placeholder="Contoh: 74 atau EMP-0074" />
                                                <x-input-error class="mt-1.5"
                                                    :messages="$errors->get('nik_karyawan')" />
                                                <p x-show="fieldErrors['nik_karyawan']"
                                                    x-text="fieldErrors['nik_karyawan'] ? fieldErrors['nik_karyawan'][0] : ''"
                                                    class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                            </div>

                                            <div>
                                                <div class="flex items-center justify-between">
                                                    <x-input-label for="user_id" :value="__('Akun Login User (Untuk ESS & Approval)')" />
                                                    <span
                                                        class="text-[10px] font-semibold text-gray-400 bg-gray-100 px-1.5 py-0.2 rounded">Opsional</span>
                                                </div>
                                                <x-select-input id="user_id" name="user_id" class="mt-1 block w-full"
                                                    :options="$userOptions" :selected="old('user_id', $empData->user_id)"
                                                    placeholder="{{ __('Pilih Akun User Login (Bisa Dikosongkan)') }}" />
                                                <x-input-error class="mt-1.5" :messages="$errors->get('user_id')" />
                                                <p x-show="fieldErrors['user_id']"
                                                    x-text="fieldErrors['user_id'] ? fieldErrors['user_id'][0] : ''"
                                                    class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                                <p class="text-[11px] text-gray-400 mt-1">
                                                    Bisa dikosongkan terlebih dahulu jika akun user login belum dibuat.
                                                </p>
                                            </div>
                                        </div>

                                        <div>
                                            <div class="flex items-center justify-between">
                                                <x-input-label for="email" :value="__('Email Resmi Karyawan')" />
                                                <span class="text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded flex items-center gap-1">
                                                    <i class="fa-solid fa-lock text-[9px]"></i> Dikelola HRD (User tidak bisa ubah)
                                                </span>
                                            </div>
                                            <div class="relative mt-1">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                                    <i class="fa-regular fa-envelope text-sm"></i>
                                                </div>
                                                <x-text-input id="email" name="email" type="email"
                                                    class="block w-full pl-10" :value="old('email', $empData->email ?? $empData->user?->email)"
                                                    placeholder="Contoh: karyawan@perusahaan.com" />
                                            </div>
                                            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />
                                            <p x-show="fieldErrors['email']"
                                                x-text="fieldErrors['email'] ? fieldErrors['email'][0] : ''"
                                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                            <p class="text-[11px] text-gray-400 mt-1">
                                                Alamat email resmi karyawan. Email ini dikelola oleh tim HRD dan tidak dapat diubah oleh user/karyawan secara mandiri.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD 1B: DATA PRIBADI --}}
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2.5 bg-slate-50/60">
                                <span
                                    class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                    <i class="fa-regular fa-id-card"></i>
                                </span>
                                <div>
                                    <h2 class="text-sm font-bold text-gray-900">Data Pribadi Karyawan</h2>
                                    <p class="text-xs text-gray-500">Informasi identitas, kewarganegaraan, dan
                                        kependudukan</p>
                                </div>
                            </div>

                            <div class="p-6">
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">

                                    {{-- Nama Lengkap --}}
                                    <div class="sm:col-span-12">
                                        <x-input-label for="name" :value="__('Nama Lengkap (Sesuai KTP)')" :required="true" />
                                        <x-text-input id="name" name="name" type="text"
                                            class="mt-1 block w-full text-base font-semibold" :value="old('name', $empData->name)" required autofocus placeholder="Contoh: Budi" />
                                        <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
                                        <p x-show="fieldErrors['name']"
                                            x-text="fieldErrors['name'] ? fieldErrors['name'][0] : ''"
                                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    </div>

                                    {{-- Jenis Kelamin --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label for="gender" :value="__('Jenis Kelamin')" />
                                        <x-select-input id="gender" name="gender" class="mt-1 block w-full"
                                            :options="$genderOptions" :selected="old('gender', $empData->gender ?? 'male')" />
                                        <x-input-error class="mt-1.5" :messages="$errors->get('gender')" />
                                        <p x-show="fieldErrors['gender']"
                                            x-text="fieldErrors['gender'] ? fieldErrors['gender'][0] : ''"
                                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    </div>

                                    {{-- Kewarganegaraan --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label for="citizenship" :value="__('Kewarganegaraan')" />
                                        <x-select-input id="citizenship" name="citizenship" class="mt-1 block w-full"
                                            :options="$citizenshipOptions" :selected="old('citizenship', $empData->citizenship ?? 'WNI')" />
                                        <x-input-error class="mt-1.5" :messages="$errors->get('citizenship')" />
                                        <p x-show="fieldErrors['citizenship']"
                                            x-text="fieldErrors['citizenship'] ? fieldErrors['citizenship'][0] : ''"
                                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    </div>

                                    {{-- Identitas / No. KTP --}}
                                    <div class="sm:col-span-12">
                                        <x-input-label for="nik_ktp" :value="__('Identitas (Nomor KTP / NIK)')" />
                                        <div class="relative mt-1">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <i class="fa-regular fa-address-card text-sm"></i>
                                            </div>
                                            <x-text-input id="nik_ktp" name="nik_ktp" type="text"
                                                class="block w-full pl-9 font-mono tracking-wider"
                                                :value="old('nik_ktp', $empData->nik_ktp)"
                                                placeholder="Contoh: -" maxlength="25" />
                                        </div>
                                        <x-input-error class="mt-1.5" :messages="$errors->get('nik_ktp')" />
                                        <p x-show="fieldErrors['nik_ktp']"
                                            x-text="fieldErrors['nik_ktp'] ? fieldErrors['nik_ktp'][0] : ''"
                                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    </div>

                                    {{-- Tempat Lahir --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label for="birth_place" :value="__('Tempat Lahir')" />
                                        <x-text-input id="birth_place" name="birth_place" type="text"
                                            class="mt-1 block w-full" :value="old('birth_place', $empData->birth_place)"
                                            placeholder="Contoh: Pemalang" />
                                        <x-input-error class="mt-1.5" :messages="$errors->get('birth_place')" />
                                        <p x-show="fieldErrors['birth_place']"
                                            x-text="fieldErrors['birth_place'] ? fieldErrors['birth_place'][0] : ''"
                                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    </div>

                                    {{-- Tanggal Lahir --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label for="birth_date" :value="__('Tanggal Lahir')" />
                                        <div class="relative mt-1">
                                            <input type="text" id="birth_date" name="birth_date"
                                                value="{{ old('birth_date', $empData->birth_date ? \Carbon\Carbon::parse($empData->birth_date)->format('Y-m-d') : '') }}"
                                                placeholder="Pilih Tanggal Lahir..."
                                                class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                                        </div>
                                        <x-input-error class="mt-1.5" :messages="$errors->get('birth_date')" />
                                        <p x-show="fieldErrors['birth_date']"
                                            x-text="fieldErrors['birth_date'] ? fieldErrors['birth_date'][0] : ''"
                                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    </div>

                                    {{-- Status Perkawinan --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label for="marital_status" :value="__('Status Perkawinan')" />
                                        <x-select-input id="marital_status" name="marital_status"
                                            class="mt-1 block w-full" :options="$maritalStatusOptions"
                                            :selected="old('marital_status', $empData->marital_status)"
                                            placeholder="Pilih Status..." />
                                        <x-input-error class="mt-1.5" :messages="$errors->get('marital_status')" />
                                        <p x-show="fieldErrors['marital_status']"
                                            x-text="fieldErrors['marital_status'] ? fieldErrors['marital_status'][0] : ''"
                                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    </div>

                                    {{-- Agama --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label for="religion" :value="__('Agama')" />
                                        <x-select-input id="religion" name="religion" class="mt-1 block w-full"
                                            :options="$religionOptions" :selected="old('religion', $empData->religion)"
                                            placeholder="Pilih Agama..." />
                                        <x-input-error class="mt-1.5" :messages="$errors->get('religion')" />
                                        <p x-show="fieldErrors['religion']"
                                            x-text="fieldErrors['religion'] ? fieldErrors['religion'][0] : ''"
                                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    </div>

                                    {{-- Golongan Darah --}}
                                    <div class="sm:col-span-6">
                                        <x-input-label for="blood_type" :value="__('Golongan Darah')" />
                                        <x-select-input id="blood_type" name="blood_type" class="mt-1 block w-full"
                                            :options="$bloodTypeOptions" :selected="old('blood_type', $empData->blood_type ?? '-')" />
                                        <x-input-error class="mt-1.5" :messages="$errors->get('blood_type')" />
                                        <p x-show="fieldErrors['blood_type']"
                                            x-text="fieldErrors['blood_type'] ? fieldErrors['blood_type'][0] : ''"
                                            class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- Field Terkait Penugasan saat pembuatan awal (agar form store valid) --}}
                        @if(!$empData->exists)
                            <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2.5 bg-slate-50/60">
                                    <span
                                        class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-briefcase"></i>
                                    </span>
                                    <div>
                                        <h2 class="text-sm font-bold text-gray-900">Penugasan & Status Payroll Awal</h2>
                                        <p class="text-xs text-gray-500">Penempatan perusahaan, jabatan, dan status PTKP</p>
                                    </div>
                                </div>

                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">
                                        <div class="sm:col-span-6">
                                            <x-input-label for="company_id" :value="__('Perusahaan (Company)')" />
                                            <x-select-input id="company_id" name="company_id" class="mt-1 block w-full"
                                                :options="$companyOptions" :selected="old('company_id', $empData->company_id)" placeholder="{{ __('Pilih Perusahaan') }}" />
                                            <x-input-error class="mt-1.5" :messages="$errors->get('company_id')" />
                                            <p x-show="fieldErrors['company_id']"
                                                x-text="fieldErrors['company_id'] ? fieldErrors['company_id'][0] : ''"
                                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                        </div>

                                        <div class="sm:col-span-6">
                                            <x-input-label for="jabatan_id" :value="__('Jabatan Karyawan')" :required="true" />
                                            <x-select-input id="jabatan_id" name="jabatan_id" class="mt-1 block w-full"
                                                :options="$jabatanOptions" :selected="old('jabatan_id', $empData->jabatan_id)" placeholder="{{ __('Pilih Jabatan') }}" required />
                                            <x-input-error class="mt-1.5" :messages="$errors->get('jabatan_id')" />
                                            <p x-show="fieldErrors['jabatan_id']"
                                                x-text="fieldErrors['jabatan_id'] ? fieldErrors['jabatan_id'][0] : ''"
                                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                        </div>

                                        <div class="sm:col-span-6">
                                            <x-input-label for="project_id" :value="__('Project')" />
                                            <x-select-input id="project_id" name="project_id" class="mt-1 block w-full"
                                                :options="$projectOptions" :selected="old('project_id', $empData->project_id)" placeholder="{{ __('Pilih Project (Opsional)') }}" />
                                            <x-input-error class="mt-1.5" :messages="$errors->get('project_id')" />
                                            <p x-show="fieldErrors['project_id']"
                                                x-text="fieldErrors['project_id'] ? fieldErrors['project_id'][0] : ''"
                                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                        </div>

                                        <div class="sm:col-span-6">
                                            <x-input-label for="status_ptkp" :value="__('Status PTKP (Pajak PPh 21)')" :required="true" />
                                            <x-select-input id="status_ptkp" name="status_ptkp" class="mt-1 block w-full"
                                                :options="$ptkpOptions" :selected="old('status_ptkp', $empData->status_ptkp)" placeholder="{{ __('Pilih Status PTKP') }}"
                                                required />
                                            <x-input-error class="mt-1.5" :messages="$errors->get('status_ptkp')" />
                                            <p x-show="fieldErrors['status_ptkp']"
                                                x-text="fieldErrors['status_ptkp'] ? fieldErrors['status_ptkp'][0] : ''"
                                                class="mt-1.5 text-xs text-red-600 font-medium"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Tombol Aksi Mandiri Tab 1 --}}
                        <div class="flex items-center justify-between pt-2">
                            <a href="{{ route('employee.index') }}"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 shadow-xs transition">
                                <i class="fa-solid fa-xmark text-gray-400"></i>
                                Batal
                            </a>

                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 disabled:opacity-50 transition cursor-pointer">
                                <span x-show="!isSubmitting" class="inline-flex items-center gap-2">
                                    <i class="fa-solid fa-check"></i>
                                    <span x-text="empExists ? 'Simpan Data Karyawan' : 'Simpan & Lanjutkan'"></span>
                                </span>
                                <span x-show="isSubmitting" x-cloak class="inline-flex items-center gap-2">
                                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                                    <span>Menyimpan...</span>
                                </span>
                            </button>
                        </div>

                    </div>
                </form>
            </div>

            {{-- ========================================================================= --}}
            {{-- TAB CONTENT 2: KARIR (BISA MULTI & SAVE BERDIRI SENDIRI VIA MODAL AJAX) --}}
            {{-- ========================================================================= --}}
            <div x-show="activeTab === 'karir'" x-cloak class="space-y-6">

                {{-- LIST RIWAYAT KARIR (MULTI-ENTRY DENGAN MODAL & TAMPILAN REAL-TIME) --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-briefcase"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider">RIWAYAT KARIR</h3>
                                <p class="text-xs text-gray-500">Perjalanan karir, penempatan kerja, tanggal efektif,
                                    dan jabatan</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs text-gray-500 font-semibold bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                <span x-text="careers.length"></span> Riwayat Tercatat
                            </span>
                            <button type="button" @click="openCareerModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition cursor-pointer">
                                <i class="fa-solid fa-plus"></i>
                                <span>Tambah Riwayat Karir</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4" x-show="careers.length > 0">
                            <template x-for="(career, index) in careers" :key="career.id">
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                    {{-- Header Card: Nomor Riwayat, Badge Posisi, & Tombol Hapus --}}
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                <i class="fa-solid fa-briefcase"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-800"
                                                x-text="'Riwayat Karir #' + (index + 1)"></span>
                                            <template x-if="index === 0">
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                                    Posisi Saat Ini
                                                </span>
                                            </template>
                                        </div>

                                        {{-- Tombol Hapus (Selalu Terlihat) --}}
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-mono text-slate-500 font-semibold hidden sm:inline"
                                                x-text="'Efektif: ' + (career.formatted_tanggal_efektif || career.tanggal_efektif)"></span>
                                            <button type="button" @click="deleteCareer(career.id)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 transition shadow-xs cursor-pointer"
                                                title="Hapus riwayat karir ini">
                                                <i class="fa-regular fa-trash-can"></i>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Grid Input Form Disabled (Max 2 Col) --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label :value="__('Jabatan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                x-bind:value="career.jabatan || '-'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Status Karyawan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="career.status_karyawan || '-'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Penempatan Kerja')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="career.penempatan_kerja || '-'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Tanggal Efektif')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="career.formatted_tanggal_efektif || career.tanggal_efektif || '-'"
                                                disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Organisasi / Divisi')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="career.organisasi || '-'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Pangkat')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="career.pangkat || '-'" disabled />
                                        </div>
                                    </div>

                                    <template x-if="career.notes">
                                        <div>
                                            <x-input-label :value="__('Catatan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-700 italic cursor-not-allowed"
                                                x-bind:value="career.notes" disabled />
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <div x-show="careers.length === 0" class="text-center py-10">
                            <div
                                class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                                <i class="fa-solid fa-briefcase"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Belum Ada Riwayat Karir</p>
                            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Klik tombol <strong>"Tambah Riwayat
                                    Karir"</strong> di atas untuk menambahkan riwayat karir baru secara fleksibel
                                melalui modal.</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ========================================================================= --}}
            {{-- TAB CONTENT 3: LAMA BEKERJA (SAVE BERDIRI SENDIRI VIA AJAX) --}}
            {{-- ========================================================================= --}}
            <div x-show="activeTab === 'lama-bekerja'" x-cloak class="space-y-6">

                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-regular fa-calendar-check"></i>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-900">Lama Bekerja</h2>
                                <p class="text-xs text-gray-500">Tanggal mulai kerja dan perhitungan akumulasi masa
                                    kerja</p>
                            </div>
                        </div>
                    </div>

                    <form novalidate method="POST" :action="empUrl('/tenure')" @input="markDirty()" @change="markDirty()"
                        @submit.prevent="submitTenure($event)">
                        @csrf

                        <div class="p-6 space-y-6">

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-6">

                                <div class="sm:col-span-6">
                                    <x-input-label for="tenure_join_date" :value="__('Mulai Kerja (Tanggal Masuk)')" />
                                    <div class="relative mt-1">
                                        <input type="text" id="tenure_join_date" name="join_date"
                                            value="{{ old('join_date', $empData->join_date ? \Carbon\Carbon::parse($empData->join_date)->format('Y-m-d') : '') }}"
                                            placeholder="Pilih Tanggal Mulai Kerja..." required
                                            class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                                    </div>
                                    <x-input-error class="mt-1.5" :messages="$errors->get('join_date')" />
                                    <p class="text-xs text-gray-400 mt-1">
                                        Tanggal awal karyawan pertama kali terhitung aktif bekerja.
                                    </p>
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label :value="__('Masa Kerja Terhitung')" />
                                    <x-text-input type="text"
                                        class="mt-1 block w-full font-bold text-blue-700 bg-slate-50 cursor-not-allowed"
                                        x-bind:value="tenureDisplay || '{{ addslashes($empData->masa_kerja ?? '-') }}'"
                                        placeholder="Otomatis dihitung sistem..." disabled />
                                    <p class="text-xs text-gray-400 mt-1">
                                        Dihitung otomatis secara real-time dari tanggal mulai kerja.
                                    </p>
                                </div>

                            </div>

                        </div>

                        <div
                            class="px-6 py-4 bg-slate-50/60 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-medium">
                                Simpan perubahan masa kerja secara mandiri.
                            </span>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 disabled:opacity-50 transition">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Lama Bekerja'"></span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            {{-- ========================================================================= --}}
            {{-- TAB CONTENT 4: PENDIDIKAN (BISA MULTI & SAVE BERDIRI SENDIRI VIA MODAL AJAX) --}}
            {{-- ========================================================================= --}}
            <div x-show="activeTab === 'pendidikan'" x-cloak class="space-y-6">

                {{-- LIST RIWAYAT PENDIDIKAN (MULTI-ENTRY DENGAN MODAL & TAMPILAN REAL-TIME) --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider">RIWAYAT PENDIDIKAN
                                </h3>
                                <p class="text-xs text-gray-500">Jenjang pendidikan, nama institusi akademik, jurusan,
                                    dan IPK</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs text-gray-500 font-semibold bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                <span x-text="educations.length"></span> Riwayat Tercatat
                            </span>
                            <button type="button" @click="openEducationModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer">
                                <i class="fa-solid fa-plus"></i>
                                <span>Tambah Riwayat Pendidikan</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4" x-show="educations.length > 0">
                            <template x-for="(edu, index) in educations" :key="edu.id">
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                    {{-- Header Card: Institusi & Tombol Hapus --}}
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                <i class="fa-solid fa-graduation-cap"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-800"
                                                x-text="edu.institusi"></span>
                                        </div>

                                        {{-- Tombol Hapus (Selalu Terlihat) --}}
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-mono text-slate-500 font-semibold hidden sm:inline"
                                                x-text="edu.period || ((edu.tahun_masuk || '') + ' - ' + (edu.tahun_lulus || ''))"></span>
                                            <button type="button" @click="deleteEducation(edu.id)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 transition shadow-xs cursor-pointer"
                                                title="Hapus riwayat pendidikan ini">
                                                <i class="fa-regular fa-trash-can"></i>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Grid Input Form Disabled (Max 2 Col) --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label :value="__('Institusi / Sekolah')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                x-bind:value="edu.institusi" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Jenjang Pendidikan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="edu.jenjang" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Jurusan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="edu.jurusan || '-'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Periode / Tahun Masuk - Lulus')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="edu.period || ((edu.tahun_masuk || '') + ' - ' + (edu.tahun_lulus || ''))"
                                                disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Nilai Akhir / IPK')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-amber-700 font-bold cursor-not-allowed"
                                                x-bind:value="edu.nilai || '-'" disabled />
                                        </div>
                                        <div x-show="edu.notes">
                                            <x-input-label :value="__('Catatan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-700 italic cursor-not-allowed"
                                                x-bind:value="edu.notes || '-'" disabled />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="educations.length === 0" class="text-center py-10">
                            <div
                                class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Belum Ada Riwayat Pendidikan</p>
                            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Klik tombol <strong>"Tambah Riwayat
                                    Pendidikan"</strong> di atas untuk menambahkan data sekolah/universitas melalui
                                modal.</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ========================================================================= --}}
            {{-- TAB CONTENT 5: PENGALAMAN KERJA (BISA MULTI & SAVE BERDIRI SENDIRI VIA MODAL AJAX) --}}
            {{-- ========================================================================= --}}
            <div x-show="activeTab === 'pengalaman-kerja'" x-cloak class="space-y-6">

                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-building-user"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider">PENGALAMAN KERJA
                                </h3>
                                <p class="text-xs text-gray-500">Riwayat pekerjaan dan pengalaman profesional sebelum
                                    bergabung</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs text-gray-500 font-semibold bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                <span x-text="experiences.length"></span> Pengalaman Tercatat
                            </span>
                            <button type="button" @click="openExperienceModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 transition cursor-pointer">
                                <i class="fa-solid fa-plus"></i>
                                <span>Tambah Pengalaman Kerja</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4" x-show="experiences.length > 0">
                            <template x-for="(exp, index) in experiences" :key="exp.id">
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                    {{-- Header Card: Jabatan, Status Terakhir, & Tombol Hapus --}}
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                <i class="fa-solid fa-briefcase"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-800"
                                                x-text="exp.jabatan + ' di ' + exp.perusahaan"></span>
                                            <template x-if="exp.is_current">
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                    Aktif / Terakhir
                                                </span>
                                            </template>
                                        </div>

                                        {{-- Tombol Hapus (Selalu Terlihat) --}}
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-mono text-slate-500 font-semibold hidden sm:inline"
                                                x-text="exp.period"></span>
                                            <button type="button" @click="deleteExperience(exp.id)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 transition shadow-xs cursor-pointer"
                                                title="Hapus riwayat pengalaman kerja ini">
                                                <i class="fa-regular fa-trash-can"></i>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Grid Input Form Disabled (Max 2 Col) --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label :value="__('Jabatan / Posisi')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                x-bind:value="exp.jabatan" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Nama Perusahaan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="exp.perusahaan" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Periode Kerja')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="exp.period" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Gaji Terakhir')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="exp.gaji_terakhir || '-'" disabled />
                                        </div>
                                        <div class="sm:col-span-2" x-show="exp.deskripsi_pekerjaan">
                                            <x-input-label :value="__('Tanggung Jawab & Deskripsi Pekerjaan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="exp.deskripsi_pekerjaan || '-'" disabled />
                                        </div>
                                        <div class="sm:col-span-2" x-show="exp.alasan_berhenti">
                                            <x-input-label :value="__('Alasan Berhenti')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="exp.alasan_berhenti || '-'" disabled />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="experiences.length === 0" class="text-center py-10">
                            <div
                                class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                                <i class="fa-solid fa-building-user"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Belum Ada Pengalaman Kerja</p>
                            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Klik tombol <strong>"Tambah
                                    Pengalaman Kerja"</strong> di atas untuk mencatat riwayat pekerjaan masa lampau
                                karyawan.</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ========================================================================= --}}
            {{-- TAB CONTENT 6: PELATIHAN & SKILL (BISA MULTI & SAVE BERDIRI SENDIRI VIA MODAL AJAX) --}}
            {{-- ========================================================================= --}}
            <div x-show="activeTab === 'pelatihan-skill'" x-cloak class="space-y-6">

                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-award"></i>
                            </span>
                            <div>
                                <h3 class="font-bold text-sm text-gray-900 uppercase tracking-wider">PELATIHAN, TRAINING
                                    & KEAHLIAN</h3>
                                <p class="text-xs text-gray-500">Daftar kursus, keahlian khusus, dan sertifikasi profesi
                                    karyawan</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="text-xs text-gray-500 font-semibold bg-white px-3 py-1.5 rounded-lg border border-gray-200">
                                <span x-text="trainings.length"></span> Pelatihan / Skill
                            </span>
                            <button type="button" @click="openTrainingModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-md shadow-amber-500/20 transition cursor-pointer">
                                <i class="fa-solid fa-plus"></i>
                                <span>Tambah Pelatihan / Skill</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4" x-show="trainings.length > 0">
                            <template x-for="(tr, index) in trainings" :key="tr.id">
                                <div class="p-5 rounded-2xl border border-slate-200 bg-white space-y-4 shadow-2xs">
                                    {{-- Header Card: Nama Pelatihan & Tombol Hapus --}}
                                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                <i class="fa-solid fa-award"></i>
                                            </div>
                                            <span class="text-xs font-bold text-slate-800"
                                                x-text="tr.nama_pelatihan"></span>
                                        </div>

                                        {{-- Tombol Hapus (Selalu Terlihat) --}}
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-mono text-slate-500 font-semibold hidden sm:inline"
                                                x-text="tr.tahun || '-'"></span>
                                            <button type="button" @click="deleteTraining(tr.id)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:text-white bg-rose-50 hover:bg-rose-600 border border-rose-200 hover:border-rose-600 transition shadow-xs cursor-pointer"
                                                title="Hapus data pelatihan ini">
                                                <i class="fa-regular fa-trash-can"></i>
                                                <span>Hapus</span>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Grid Input Form Disabled (Max 2 Col) --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <x-input-label :value="__('Nama Pelatihan / Keahlian')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-900 font-bold cursor-not-allowed"
                                                x-bind:value="tr.nama_pelatihan" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Lembaga Penyelenggara')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="tr.penyelenggara" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Tahun Pelaksanaan')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="tr.tahun || '-'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Nomor Sertifikat / Lisensi')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full font-mono bg-slate-50 text-blue-700 font-bold cursor-not-allowed"
                                                x-bind:value="tr.nomor_sertifikat || '-'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Masa Berlaku')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-800 cursor-not-allowed"
                                                x-bind:value="tr.masa_berlaku || 'Seumur Hidup'" disabled />
                                        </div>
                                        <div>
                                            <x-input-label :value="__('Deskripsi / Materi')" />
                                            <x-text-input type="text"
                                                class="mt-1 block w-full bg-slate-50 text-slate-700 cursor-not-allowed"
                                                x-bind:value="tr.deskripsi || '-'" disabled />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div x-show="trainings.length === 0" class="text-center py-10">
                            <div
                                class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                                <i class="fa-solid fa-award"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-700">Belum Ada Pelatihan atau Keahlian</p>
                            <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">Klik tombol <strong>"Tambah Pelatihan
                                    / Skill"</strong> di atas untuk mencatat sertifikasi dan keahlian karyawan.</p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- ========================================================================= --}}
            {{-- TAB CONTENT 7: PENUGASAN & STATUS PAYROLL (SAVE BERDIRI SENDIRI VIA AJAX) --}}
            {{-- ========================================================================= --}}
            <div x-show="activeTab === 'penugasan-payroll'" x-cloak class="space-y-6">

                <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/60">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-briefcase"></i>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-900">Penugasan & Status Payroll</h2>
                                <p class="text-xs text-gray-500">Perusahaan, jabatan struktural, project, dan status
                                    PTKP pajak</p>
                            </div>
                        </div>
                    </div>

                    <form novalidate method="POST" :action="empUrl('/assignment')" @input="markDirty()" @change="markDirty()"
                        @submit.prevent="submitAssignment($event)">
                        @csrf

                        <div class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-5">

                                {{-- Company --}}
                                <div class="sm:col-span-6">
                                    <x-input-label for="assign_company_id" :value="__('Perusahaan (Company)')" />
                                    <x-select-input id="assign_company_id" name="company_id" class="mt-1 block w-full"
                                        :options="$companyOptions" :selected="old('company_id', $empData->company_id)"
                                        placeholder="{{ __('Pilih Perusahaan') }}" />
                                    <x-input-error class="mt-1.5" :messages="$errors->get('company_id')" />
                                </div>

                                {{-- Jabatan --}}
                                <div class="sm:col-span-6">
                                    <x-input-label for="assign_jabatan_id" :value="__('Jabatan Karyawan')" :required="true" />
                                    <x-select-input id="assign_jabatan_id" name="jabatan_id" class="mt-1 block w-full"
                                        :options="$jabatanOptions" :selected="old('jabatan_id', $empData->jabatan_id)"
                                        placeholder="{{ __('Pilih Jabatan') }}" required />
                                    <x-input-error class="mt-1.5" :messages="$errors->get('jabatan_id')" />
                                </div>

                                {{-- Project --}}
                                <div class="sm:col-span-6">
                                    <x-input-label for="assign_project_id" :value="__('Project')" />
                                    <x-select-input id="assign_project_id" name="project_id" class="mt-1 block w-full"
                                        :options="$projectOptions" :selected="old('project_id', $empData->project_id)"
                                        placeholder="{{ __('Pilih Project (Opsional)') }}" />
                                    <x-input-error class="mt-1.5" :messages="$errors->get('project_id')" />
                                </div>

                                {{-- Status PTKP --}}
                                <div class="sm:col-span-6">
                                    <x-input-label for="assign_status_ptkp" :value="__('Status PTKP (Pajak PPh 21)')" :required="true" />
                                    <x-select-input id="assign_status_ptkp" name="status_ptkp" class="mt-1 block w-full"
                                        :options="$ptkpOptions" :selected="old('status_ptkp', $empData->status_ptkp)"
                                        placeholder="{{ __('Pilih Status PTKP') }}" required />
                                    <x-input-error class="mt-1.5" :messages="$errors->get('status_ptkp')" />
                                    <p class="text-xs text-gray-500 mt-1">
                                        Status PTKP menentukan skema tarif TER pajak PPh 21 pada modul penggajian.
                                    </p>
                                </div>

                            </div>
                        </div>

                        <div
                            class="px-6 py-4 bg-slate-50/60 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-medium">
                                Simpan perubahan penugasan dan payroll secara mandiri.
                            </span>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-md shadow-amber-500/20 disabled:opacity-50 transition">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Penugasan & Payroll'"></span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            {{-- ========================================================================= --}}
            {{-- TAB CONTENT 6: DATA BPJS (SAVE BERDIRI SENDIRI VIA AJAX) --}}
            {{-- ========================================================================= --}}
            <div x-show="activeTab === 'bpjs'" x-cloak class="space-y-6">

                <form novalidate method="POST" :action="empUrl('/bpjs')" @input="markDirty()" @change="markDirty()"
                    @submit.prevent="submitBpjs($event)"
                    class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                    @csrf

                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                    <i class="fa-solid fa-shield-heart text-blue-600"></i>
                                    Kelola Data BPJS Karyawan
                                </h2>
                                <p class="text-xs text-gray-500 mt-1">
                                    Nomor kepesertaan atau nominal komponen BPJS Kesehatan dan Ketenagakerjaan karyawan.
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <i class="fa-solid fa-circle-check text-[10px]"></i>
                                Subsidi Perusahaan
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        @if(empty($bpjsComponents) || $bpjsComponents->isEmpty())
                            <div
                                class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-2">
                                <i class="fa-solid fa-circle-exclamation text-amber-500"></i>
                                <span>Belum ada komponen payroll bertipe <strong>subsidi</strong> (BPJS). Silakan
                                    konfigurasi komponen subsidi di modul Komponen Payroll.</span>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach($bpjsComponents as $payrollComp)
                                    <div
                                        class="p-4 rounded-xl border border-gray-100 bg-slate-50/50 hover:bg-white hover:border-blue-200 transition">
                                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                                            <div class="sm:col-span-5">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-bold shrink-0">
                                                        <i
                                                            class="fa-solid {{ str_contains(strtolower($payrollComp->name), 'kesehatan') ? 'fa-heart-pulse' : 'fa-briefcase' }}"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="text-xs font-bold text-gray-900">{{ $payrollComp->name }}
                                                        </h4>
                                                        <span
                                                            class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-mono uppercase bg-slate-200 text-slate-700">
                                                            {{ ucfirst($payrollComp->type) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="sm:col-span-7">
                                                <x-input-label for="bpjs_{{ $payrollComp->id }}" :value="__('Nomor Kepesertaan / Nilai')" />
                                                <x-text-input id="bpjs_{{ $payrollComp->id }}"
                                                    name="bpjs[{{ $payrollComp->id }}]" type="text" class="mt-1 block w-full"
                                                    :value="old('bpjs.' . $payrollComp->id, $bpjsData[$payrollComp->id] ?? '')"
                                                    placeholder="Contoh: 000123456789 atau nilai nominal" />
                                                <x-input-error class="mt-1" :messages="$errors->get('bpjs.' . $payrollComp->id)" />
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="px-6 py-4 bg-slate-50/60 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-xs text-gray-500 font-medium">
                            Simpan perubahan BPJS secara mandiri.
                        </span>
                        <button type="submit" :disabled="isSubmitting"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 disabled:opacity-50 transition">
                            <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                            <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Data BPJS'"></span>
                        </button>
                    </div>
                </form>

            </div>

        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL PERINGATAN UNSAVED CHANGES SAAT PINDAH TAB --}}
        {{-- ========================================================================= --}}
        <div x-show="confirmModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="confirmModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                    @click="confirmModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="confirmModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                    <div class="bg-white p-6">
                        <div class="sm:flex sm:items-start gap-4">
                            <div
                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-2xl bg-amber-100 text-amber-600 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:text-left">
                                <h3 class="text-base font-bold text-gray-900" id="modal-title">
                                    Perubahan Belum Disimpan!
                                </h3>
                                <div class="mt-2">
                                    <p class="text-xs text-gray-500 leading-relaxed">
                                        Anda memiliki perubahan data di tab saat ini yang belum disimpan. Jika Anda
                                        berpindah tab sekarang, perubahan tersebut akan hilang.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 sm:flex sm:flex-row-reverse gap-2">
                        <button type="button" @click="confirmSwitchTab()"
                            class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-xs px-4 py-2 bg-amber-600 text-xs font-bold text-white hover:bg-amber-700 sm:w-auto transition">
                            Pindah & Abaikan Perubahan
                        </button>
                        <button type="button" @click="confirmModalOpen = false"
                            class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-xl border border-gray-200 shadow-xs px-4 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 sm:w-auto transition">
                            Tetap di Tab Ini (Simpan Dulu)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL TAMBAH RIWAYAT KARIR (MULTI) --}}
        {{-- ========================================================================= --}}
        <div x-show="careerModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="career-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="careerModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                    @click="careerModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="careerModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">

                    {{-- Modal Header --}}
                    <div class="px-6 py-4 bg-slate-50 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span
                                class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-briefcase"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="career-modal-title">
                                    Tambah Riwayat Karir
                                </h3>
                                <p class="text-xs text-gray-500">Isi data riwayat jabatan, penempatan, dan status
                                    karyawan baru</p>
                            </div>
                        </div>
                        <button type="button" @click="careerModalOpen = false"
                            class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body / Form --}}
                    <form novalidate method="POST" :action="empUrl('/career')" @submit.prevent="submitCareer($event)">
                        @csrf

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_status_karyawan" :value="__('Status Karyawan')" :required="true" />
                                    <x-select-input id="modal_status_karyawan" name="status_karyawan"
                                        class="mt-1 block w-full" :options="$statusKaryawanOptions"
                                        :selected="old('status_karyawan', 'PKWT')" required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_penempatan_kerja" :value="__('Penempatan Kerja')" :required="true" />
                                    <x-select-input id="modal_penempatan_kerja" name="penempatan_kerja"
                                        class="mt-1 block w-full" :options="$penempatanKerjaOptions"
                                        :selected="old('penempatan_kerja', 'Baru Direkrut')" required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="career_tanggal_efektif" :value="__('Tanggal Efektif')" :required="true" />
                                    <div class="relative mt-1">
                                        <input type="text" id="career_tanggal_efektif" name="tanggal_efektif"
                                            value="{{ old('tanggal_efektif', date('Y-m-d')) }}"
                                            placeholder="Pilih Tanggal Efektif..." required
                                            class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                                    </div>
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_organisasi" :value="__('Organisasi / Divisi')" />
                                    <x-select-input id="modal_organisasi" name="organisasi" class="mt-1 block w-full"
                                        :options="$organisasiOptions" :selected="old('organisasi', 'IT')" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_pangkat" :value="__('Pangkat')" />
                                    <x-select-input id="modal_pangkat" name="pangkat" class="mt-1 block w-full"
                                        :options="$pangkatOptions" :selected="old('pangkat', 'Staff')" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_career_jabatan" :value="__('Jabatan')" :required="true" />
                                    <x-text-input id="modal_career_jabatan" name="jabatan" type="text"
                                        class="mt-1 block w-full" :value="old('jabatan', $empData->jabatan?->jabatan ?? '')" placeholder="Contoh: Back-end Developer Staff" required />
                                </div>

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_career_notes" :value="__('Catatan Karir (Opsional)')" />
                                    <x-text-input id="modal_career_notes" name="notes" type="text"
                                        class="mt-1 block w-full" :value="old('notes')"
                                        placeholder="Contoh: Evaluasi tahunan, promosi berkala, dsb." />
                                </div>

                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="careerModalOpen = false"
                                class="inline-flex justify-center rounded-xl border border-gray-200 shadow-xs px-4 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-500/20 disabled:opacity-50 transition cursor-pointer">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Riwayat Karir'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL TAMBAH RIWAYAT PENDIDIKAN (MULTI) --}}
        {{-- ========================================================================= --}}
        <div x-show="educationModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="education-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="educationModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                    @click="educationModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="educationModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">

                    {{-- Modal Header --}}
                    <div class="px-6 py-4 bg-slate-50 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span
                                class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-graduation-cap"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="education-modal-title">
                                    Tambah Riwayat Pendidikan
                                </h3>
                                <p class="text-xs text-gray-500">Isi jenjang pendidikan, nama instansi akademik, dan
                                    jurusan</p>
                            </div>
                        </div>
                        <button type="button" @click="educationModalOpen = false"
                            class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body / Form --}}
                    <form novalidate method="POST" :action="empUrl('/education')" @submit.prevent="submitEducation($event)">
                        @csrf

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_jenjang" :value="__('Jenjang Pendidikan Terakhir / Riwayat')" :required="true" />
                                    <x-select-input id="modal_edu_jenjang" name="jenjang" class="mt-1 block w-full"
                                        :options="$educationLevelOptions" :selected="old('jenjang', 'D4/S1')"
                                        required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_institusi" :value="__('Nama Institusi Pendidikan')" :required="true" />
                                    <x-text-input id="modal_edu_institusi" name="institusi" type="text"
                                        class="mt-1 block w-full" :value="old('institusi')"
                                        placeholder="Contoh: Universitas Brawijaya / SMK Negeri 1 Malang" required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_jurusan" :value="__('Jurusan / Program Studi (Opsional)')" />
                                    <x-text-input id="modal_edu_jurusan" name="jurusan" type="text"
                                        class="mt-1 block w-full" :value="old('jurusan')"
                                        placeholder="Contoh: Teknik Informatika / Manajemen" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_nilai" :value="__('IPK / Nilai Akhir (Opsional)')" />
                                    <x-text-input id="modal_edu_nilai" name="nilai" type="text"
                                        class="mt-1 block w-full" :value="old('nilai')"
                                        placeholder="Contoh: 3.75 / 88.5" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_tahun_masuk" :value="__('Tahun Masuk (Opsional)')" />
                                    <x-text-input id="modal_edu_tahun_masuk" name="tahun_masuk" type="text"
                                        class="mt-1 block w-full font-mono" :value="old('tahun_masuk')"
                                        placeholder="Contoh: 2018" maxlength="10" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_edu_tahun_lulus" :value="__('Tahun Lulus (Opsional)')" />
                                    <x-text-input id="modal_edu_tahun_lulus" name="tahun_lulus" type="text"
                                        class="mt-1 block w-full font-mono" :value="old('tahun_lulus')"
                                        placeholder="Contoh: 2022" maxlength="10" />
                                </div>

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_edu_notes" :value="__('Catatan Tambahan (Opsional)')" />
                                    <x-text-input id="modal_edu_notes" name="notes" type="text"
                                        class="mt-1 block w-full" :value="old('notes')"
                                        placeholder="Contoh: Lulusan terbaik / Cumlaude" />
                                </div>

                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="educationModalOpen = false"
                                class="inline-flex justify-center rounded-xl border border-gray-200 shadow-xs px-4 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-md shadow-emerald-500/20 disabled:opacity-50 transition cursor-pointer">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Riwayat Pendidikan'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL TAMBAH RIWAYAT PENGALAMAN KERJA (MULTI) --}}
        {{-- ========================================================================= --}}
        <div x-show="experienceModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="exp-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="experienceModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                    @click="experienceModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="experienceModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">

                    {{-- Modal Header --}}
                    <div class="px-6 py-4 bg-slate-50 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span
                                class="w-9 h-9 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-building-user"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="exp-modal-title">
                                    Tambah Pengalaman Kerja
                                </h3>
                                <p class="text-xs text-gray-500">Isi data riwayat pekerjaan dan perusahaan terdahulu
                                    karyawan</p>
                            </div>
                        </div>
                        <button type="button" @click="experienceModalOpen = false"
                            class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body / Form --}}
                    <form novalidate method="POST" :action="empUrl('/experience')" @submit.prevent="submitExperience($event)">
                        @csrf

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_exp_perusahaan" :value="__('Nama Perusahaan')" :required="true" />
                                    <x-text-input id="modal_exp_perusahaan" name="perusahaan" type="text"
                                        class="mt-1 block w-full" placeholder="Contoh: PT Teknologi Bangsa" required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_exp_jabatan" :value="__('Jabatan / Posisi')" :required="true" />
                                    <x-text-input id="modal_exp_jabatan" name="jabatan" type="text"
                                        class="mt-1 block w-full" placeholder="Contoh: Senior Web Developer" required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="exp_tanggal_mulai" :value="__('Tanggal Mulai Kerja')" />
                                    <div class="relative mt-1">
                                        <input type="text" id="exp_tanggal_mulai" name="tanggal_mulai"
                                            placeholder="Pilih Tanggal Mulai..."
                                            class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                                    </div>
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="exp_tanggal_selesai" :value="__('Tanggal Berakhir Kerja')" />
                                    <div class="relative mt-1">
                                        <input type="text" id="exp_tanggal_selesai" name="tanggal_selesai"
                                            placeholder="Pilih Tanggal Selesai..."
                                            class="border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl py-2 px-3 text-sm shadow-xs w-full bg-white cursor-pointer" />
                                    </div>
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_exp_gaji" :value="__('Gaji Terakhir (Opsional)')" />
                                    <x-text-input id="modal_exp_gaji" name="gaji_terakhir" type="text"
                                        class="mt-1 block w-full" placeholder="Contoh: Rp 8.000.000" />
                                </div>

                                <div class="sm:col-span-6 flex items-center pt-6">
                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="is_current" value="1"
                                            class="rounded border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500">
                                        <span class="text-xs font-semibold text-gray-700">Masih bekerja di sini saat
                                            ini</span>
                                    </label>
                                </div>

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_exp_deskripsi" :value="__('Tanggung Jawab & Pekerjaan (Opsional)')" />
                                    <textarea id="modal_exp_deskripsi" name="deskripsi_pekerjaan" rows="2"
                                        class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-xl shadow-xs text-sm"
                                        placeholder="Ringkasan tugas, portofolio pencapaian, atau tanggung jawab utama..."></textarea>
                                </div>

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_exp_alasan" :value="__('Alasan Berhenti / Pindah (Opsional)')" />
                                    <x-text-input id="modal_exp_alasan" name="alasan_berhenti" type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Contoh: Kontrak habis / Mencari tantangan baru" />
                                </div>

                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="experienceModalOpen = false"
                                class="inline-flex justify-center rounded-xl border border-gray-200 shadow-xs px-4 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 disabled:opacity-50 transition cursor-pointer">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Pengalaman Kerja'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL TAMBAH PELATIHAN & SKILL (MULTI) --}}
        {{-- ========================================================================= --}}
        <div x-show="trainingModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="training-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="trainingModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
                    @click="trainingModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="trainingModalOpen" x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">

                    {{-- Modal Header --}}
                    <div class="px-6 py-4 bg-slate-50 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span
                                class="w-9 h-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-sm">
                                <i class="fa-solid fa-award"></i>
                            </span>
                            <div>
                                <h3 class="text-base font-bold text-gray-900" id="training-modal-title">
                                    Tambah Pelatihan / Training / Keahlian
                                </h3>
                                <p class="text-xs text-gray-500">Isi data kursus, sertifikasi kompetensi, atau lisensi
                                    keahlian</p>
                            </div>
                        </div>
                        <button type="button" @click="trainingModalOpen = false"
                            class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    {{-- Modal Body / Form --}}
                    <form novalidate method="POST" :action="empUrl('/training')" @submit.prevent="submitTraining($event)">
                        @csrf

                        <div class="p-6 space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_tr_nama" :value="__('Nama Pelatihan / Kursus / Keahlian')" :required="true" />
                                    <x-text-input id="modal_tr_nama" name="nama_pelatihan" type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Contoh: Certified Scrum Master / Fullstack Laravel Architecture"
                                        required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_tr_penyelenggara" :value="__('Lembaga / Penyelenggara')" :required="true" />
                                    <x-text-input id="modal_tr_penyelenggara" name="penyelenggara" type="text"
                                        class="mt-1 block w-full" placeholder="Contoh: BNSP / Udemy / Dicoding"
                                        required />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_tr_tahun" :value="__('Tahun / Periode')" />
                                    <x-text-input id="modal_tr_tahun" name="tahun" type="text"
                                        class="mt-1 block w-full font-mono" placeholder="Contoh: 2024" maxlength="20" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_tr_nomor" :value="__('Nomor Sertifikat / Lisensi (Opsional)')" />
                                    <x-text-input id="modal_tr_nomor" name="nomor_sertifikat" type="text"
                                        class="mt-1 block w-full font-mono" placeholder="Contoh: CERT-2024-88912" />
                                </div>

                                <div class="sm:col-span-6">
                                    <x-input-label for="modal_tr_masa" :value="__('Masa Berlaku (Opsional)')" />
                                    <x-text-input id="modal_tr_masa" name="masa_berlaku" type="text"
                                        class="mt-1 block w-full" placeholder="Contoh: Seumur Hidup / 31 Des 2027" />
                                </div>

                                <div class="sm:col-span-12">
                                    <x-input-label for="modal_tr_deskripsi" :value="__('Keterangan / Keahlian yang Diperoleh (Opsional)')" />
                                    <x-text-input id="modal_tr_deskripsi" name="deskripsi" type="text"
                                        class="mt-1 block w-full"
                                        placeholder="Contoh: Menguasai Docker, CI/CD pipeline, dan unit testing" />
                                </div>

                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-slate-50 px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button type="button" @click="trainingModalOpen = false"
                                class="inline-flex justify-center rounded-xl border border-gray-200 shadow-xs px-4 py-2 bg-white text-xs font-bold text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-md shadow-amber-500/20 disabled:opacity-50 transition cursor-pointer">
                                <i class="fa-solid" :class="isSubmitting ? 'fa-circle-notch fa-spin' : 'fa-check'"></i>
                                <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Pelatihan & Skill'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
