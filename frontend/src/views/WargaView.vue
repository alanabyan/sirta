<script setup>
	import { onMounted, reactive, ref } from 'vue';
	import { useRoute } from 'vue-router';
	import { Plus, Search, Pencil, Trash2, Users } from 'lucide-vue-next';
	import api, { errorMessage } from '@/api';
	import { useAuth } from '@/stores/auth';
	import { useUi } from '@/stores/ui';
	import { useList } from '@/composables/useList';
	import { useSave } from '@/composables/useSave';
	import { inisial, formatTanggal } from '@/utils'
	import PageHeader from '@/components/PageHeader.vue';
	import BaseModal from '@/components/BaseModal.vue';
	import FormField from '@/components/FormField.vue';
	import StatusBadge from '@/components/StatusBadge.vue';
	import EmptyState from '@/components/EmptyState.vue';
	import PaginationBar from '@/components/PaginationBar.vue';

	const auth = useAuth();
	const ui = useUi();
	const route = useRoute();
	const list = useList('/warga', {
		filters: { jenis_kelamin: '', status: '' },
	});
	const { saving, errors, save } = useSave('/warga');

	const ringkasan = ref({ total: 0, laki_laki: 0, perempuan: 0, aktif: 0 });
	const keluargaOpsi = ref([]);
	const loadRingkasan = async () =>
		(ringkasan.value = (await api.get('/warga/ringkasan')).data.data);

	const kosong = () => ({
		id: null,
		nik: '',
		nama: '',
		jenis_kelamin: 'Laki-laki',
		tanggal_lahir: '',
		pekerjaan: '',
		telepon: '',
		status: 'Aktif',
		keluarga_id: '',
		hubungan_keluarga: '',
	});
	const form = reactive(kosong());
	const showForm = ref(false);

	async function buka(w) {
		// NIK lengkap tidak ada di daftar; ambil khusus saat form ubah dibuka.
		let nik = '';
		if (w) {
			try {
				nik = (await api.get(`/warga/${w.id}/nik`, { params: { untuk: 'ubah' } })).data.data.nik;
			} catch (e) {
				return ui.error(errorMessage(e));
			}
		}
		Object.assign(
			form,
			kosong(),
			w
				? {
						...w,
						nik,
						keluarga_id: w.keluarga_id ?? '',
						hubungan_keluarga: w.hubungan_keluarga ?? '',
						pekerjaan: w.pekerjaan ?? '',
						telepon: w.telepon ?? '',
					}
				: {},
		);
		errors.value = {};
		showForm.value = true;
	}

	async function simpan() {
		const { id, ...payload } = form;
		payload.keluarga_id = payload.keluarga_id || null;
		payload.hubungan_keluarga = payload.hubungan_keluarga || null;
		if (await save(id, payload)) {
			showForm.value = false;
			list.load();
			loadRingkasan();
		}
	}

	async function hapus(w) {
		if (
			await list.remove(w, {
				title: `Hapus data ${w.nama}?`,
				message:
					'Data warga beserta permohonannya akan ikut terhapus dan tidak dapat dikembalikan.',
			})
		)
			loadRingkasan();
	}

	onMounted(async () => {
		loadRingkasan();
		keluargaOpsi.value = (
			await api.get('/keluarga', { params: { all: 1 } })
		).data.data;
		if (route.query.baru && auth.canWrite) buka();
	});
</script>

<template>
	<PageHeader
		title="Data Warga"
		description="Daftar seluruh penduduk RT. Data ini dipakai untuk membuat permohonan dan surat."
	>
		<button
			v-if="auth.canWrite"
			class="btn btn-primary"
			@click="buka()"
		>
			<Plus :size="18" /> Tambah warga
		</button>
	</PageHeader>

	<div
		class="grid grid-4"
		style="margin-bottom: 16px"
	>
		<div class="card card-pad">
			<div class="muted small">Total warga</div>
			<b class="big">{{ ringkasan.total }}</b>
		</div>
		<div class="card card-pad">
			<div class="muted small">Laki-laki</div>
			<b class="big">{{ ringkasan.laki_laki }}</b>
		</div>
		<div class="card card-pad">
			<div class="muted small">Perempuan</div>
			<b class="big">{{ ringkasan.perempuan }}</b>
		</div>
		<div class="card card-pad">
			<div class="muted small">Berstatus aktif</div>
			<b class="big">{{ ringkasan.aktif }}</b>
		</div>
	</div>

	<div class="card">
		<div class="toolbar">
			<div class="search">
				<Search :size="18" /><input
					v-model="list.q.value"
					class="input"
					placeholder="Cari nama, NIK, atau pekerjaan…"
					aria-label="Cari warga"
				/>
			</div>
			<select
				v-model="list.filter.jenis_kelamin"
				class="select"
				aria-label="Jenis kelamin"
			>
				<option value="">Semua jenis kelamin</option>
				<option>Laki-laki</option>
				<option>Perempuan</option>
			</select>
			<select
				v-model="list.filter.status"
				class="select"
				aria-label="Status"
			>
				<option value="">Semua status</option>
				<option>Aktif</option>
				<option>Pindah</option>
				<option>Meninggal</option>
			</select>
		</div>

		<div
			v-if="list.loading.value && !list.items.value.length"
			class="loading"
		>
			<div class="spinner" />
		</div>
		<div
			v-else-if="list.error.value"
			class="callout tone-bad"
			style="margin: 16px"
		>
			{{ list.error.value }}
		</div>
		<EmptyState
			v-else-if="!list.items.value.length"
			:icon="Users"
			title="Warga tidak ditemukan"
			:text="
				list.q.value
					? 'Coba kata kunci lain.'
					: 'Mulai dengan menambahkan warga pertama.'
			"
		/>
		<div
			v-else
			class="table-wrap"
		>
			<table class="table">
				<thead>
					<tr>
						<th>Warga</th>
						<th>NIK</th>
						<th>Umur</th>
						<th>Keluarga</th>
						<th>Pekerjaan</th>
						<th>Status</th>
						<th v-if="auth.canWrite" />
					</tr>
				</thead>
				<tbody>
					<tr
						v-for="w in list.items.value"
						:key="w.id"
					>
						<td>
							<div class="row">
								<span class="avatar">{{ inisial(w.nama) }}</span>
								<div>
									<div class="cell-title">{{ w.nama }}</div>
									<div class="cell-sub">
										{{ w.jenis_kelamin }} · lahir
										{{ formatTanggal(w.tanggal_lahir, true) }}
									</div>
								</div>
							</div>
						</td>
						<td class="mono">{{ w.nik_samar || '—' }}</td>
						<td>{{ w.umur }} th</td>
						<td>
							<template v-if="w.keluarga"
								>{{ w.keluarga.kepala_keluarga }}
								<div class="cell-sub">
									{{ w.hubungan_keluarga || 'Anggota' }} ·
									{{ w.keluarga.alamat }}
								</div></template
							>
							<span
								v-else
								class="faint"
								>Belum terhubung</span
							>
						</td>
						<td>{{ w.pekerjaan || '—' }}</td>
						<td><StatusBadge :status="w.status" /></td>
						<td
							v-if="auth.canWrite"
							class="actions"
						>
							<button
								class="btn btn-icon"
								title="Ubah"
								:aria-label="`Ubah ${w.nama}`"
								@click="buka(w)"
							>
								<Pencil :size="17" />
							</button>
							<button
								v-if="auth.canDecide"
								class="btn btn-icon"
								title="Hapus"
								:aria-label="`Hapus ${w.nama}`"
								@click="hapus(w)"
							>
								<Trash2 :size="17" />
							</button>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<PaginationBar
			v-bind="list.meta"
			@change="list.goto"
		/>
	</div>

	<BaseModal
		v-if="showForm"
		:title="form.id ? 'Ubah data warga' : 'Tambah warga'"
		subtitle="Kolom bertanda * wajib diisi."
		size="lg"
		:saving="saving"
		@close="showForm = false"
		@save="simpan"
	>
		<div class="form-grid">
			<FormField
				label="NIK"
				:error="errors.nik"
				hint="16 digit sesuai KTP"
				required
			>
				<input
					v-model="form.nik"
					class="input mono"
					inputmode="numeric"
					maxlength="16"
					placeholder="3275…"
					required
				/>
			</FormField>
			<FormField
				label="Nama lengkap"
				:error="errors.nama"
				required
				><input
					v-model="form.nama"
					class="input"
					required
			/></FormField>
			<FormField
				label="Jenis kelamin"
				:error="errors.jenis_kelamin"
				required
			>
				<select
					v-model="form.jenis_kelamin"
					class="select"
				>
					<option>Laki-laki</option>
					<option>Perempuan</option>
				</select>
			</FormField>
			<FormField
				label="Tanggal lahir"
				:error="errors.tanggal_lahir"
				required
				><input
					v-model="form.tanggal_lahir"
					type="date"
					class="input"
					required
			/></FormField>
			<FormField
				label="Pekerjaan"
				:error="errors.pekerjaan"
				><input
					v-model="form.pekerjaan"
					class="input"
			/></FormField>
			<FormField
				label="No. telepon"
				:error="errors.telepon"
				><input
					v-model="form.telepon"
					class="input"
					inputmode="tel"
			/></FormField>
			<FormField
				label="Keluarga (KK)"
				:error="errors.keluarga_id"
				hint="Pilih bila warga sudah terdaftar dalam sebuah KK"
			>
				<select
					v-model="form.keluarga_id"
					class="select"
				>
					<option value="">— Belum terhubung —</option>
					<option
						v-for="k in keluargaOpsi"
						:key="k.id"
						:value="k.id"
					>
						{{ k.kepala_keluarga }} · {{ k.alamat }}
					</option>
				</select>
			</FormField>
			<FormField
				label="Hubungan dalam keluarga"
				:error="errors.hubungan_keluarga"
			>
				<select
					v-model="form.hubungan_keluarga"
					class="select"
				>
					<option value="">—</option>
					<option>Kepala Keluarga</option>
					<option>Istri</option>
					<option>Anak</option>
					<option>Famili Lain</option>
				</select>
			</FormField>
			<FormField
				label="Status"
				:error="errors.status"
				required
			>
				<select
					v-model="form.status"
					class="select"
				>
					<option>Aktif</option>
					<option>Pindah</option>
					<option>Meninggal</option>
				</select>
			</FormField>
		</div>
	</BaseModal>
</template>

<style scoped>
	.big {
		font-size: 28px;
		letter-spacing: -0.02em;
		display: block;
		margin-top: 2px;
	}
</style>
